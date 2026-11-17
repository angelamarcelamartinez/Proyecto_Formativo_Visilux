<?php

// Clase de apoyo con todo el proceso de alta de una óptica nueva (desde /planes) y su aprobación o rechazo.
namespace App\Support;

// CredencialesAccesoMail: el correo con usuario y contraseña temporal.
use App\Mail\CredencialesAccesoMail;
use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\PaginaEmpresa;
use App\Models\Plan;
use App\Models\Usuario;
// DB: para usar transacciones (todo se guarda junto o nada).
use Illuminate\Support\Facades\DB;
// Hash: encripta contraseñas (en la BD nunca queda la contraseña real).
use Illuminate\Support\Facades\Hash;
// Mail: envía correos.
use Illuminate\Support\Facades\Mail;
// Str: genera contraseñas y textos aleatorios.
use Illuminate\Support\Str;

/**
 * Una óptica nueva se registra desde /planes:
 *   1. registrar(): se guardan la óptica (estado "pendiente"), su administrador
 *      (sin acceso todavía) y la licencia "pendiente" con los datos del pago simulado.
 *   2. El superadmin la ve en "Pagos por aprobar".
 *   3. aprobar(): se activa todo, se genera una contraseña temporal y se envía al correo
 *      de la ÓPTICA (el usuario es el correo del administrador). En su primer ingreso el
 *      administrador está obligado a cambiarla.
 *      rechazar(): se borra el registro para que puedan intentarlo de nuevo.
 */
// Flujo: registrar() → (superadmin revisa) → aprobar() o rechazar().
class RegistroOptica
{
    // id_estado de la tabla `estado`
    // Estados de la tabla estado: 1 = Activo.
    public const USUARIO_ACTIVO = 1;
    // 3 = Pendiente (tiene contraseña temporal y debe cambiarla al entrar).
    public const USUARIO_PENDIENTE = 3;

    /**
     * @param array $datos Datos validados del formulario (óptica y administrador).
     * @param array|null $pago Resultado de PagoSimulado::cobrar(), o null si el plan es gratis.
     */
    // Crea todo lo de una óptica nueva. Recibe los datos validados, el plan, el pago simulado (o null si es gratis) y la ruta del PDF.
    public static function registrar(array $datos, Plan $plan, ?array $pago, ?string $rutaCamara = null): Empresa
    {
        // Transacción: si cualquier paso falla, se deshace todo y no queda una óptica a medias.
        return DB::transaction(function () use ($datos, $plan, $pago, $rutaCamara) {
            // 1) Crea la óptica en estado "pendiente": todavía no puede usar el sistema.
            $empresa = Empresa::create([
                'nit' => $datos['nit'],
                'nombre' => $datos['nombre'],
                // Dirección amigable única generada del nombre (Empresa::slugDisponible).
                'slug' => Empresa::slugDisponible($datos['nombre']),
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'direccion' => $datos['direccion'] ?? null,
                'ciudad' => $datos['ciudad'],
                'estado' => 'pendiente',
                // Todavía no ha usado la prueba gratis.
                'prueba_usada' => 0,
                'fecha_registro' => now(),
                // PDF de la Cámara de Comercio: el superadmin lo revisa y confirma el NIT al aprobar.
                // Ruta del PDF guardado ('' si no hay).
                'camara_comercio' => $rutaCamara ?? '',
            ]);

            // El administrador queda creado pero sin poder entrar: su contraseña es
            // aleatoria y nadie la conoce. La real se genera al aprobar.
            // 2) Crea al administrador, pero sin que pueda entrar: su contraseña es aleatoria y nadie la conoce.
            Usuario::create([
                'documento' => $datos['admin_documento'],
                'nombres' => $datos['admin_nombres'],
                'apellido' => $datos['admin_apellido'] ?? '',
                'telefono' => $datos['admin_telefono'],
                'email' => $datos['admin_email'],
                // Str::random(40) = 40 caracteres al azar; Hash::make los encripta. La contraseña real se crea al aprobar.
                'password' => Hash::make(Str::random(40)),
                'fecha_creacion' => now(),
                // Rol administrador (1), tomado de config/visioptica.php.
                'id_rol' => config('visioptica.rol_admin'),
                'id_tipo_docu' => 1,
                // Estado 3 = pendiente.
                'id_estado' => self::USUARIO_PENDIENTE,
                // Lo vincula a su óptica.
                'nit_empresa' => $empresa->nit,
            ]);

            // 3) Crea la licencia en estado "pendiente" con los datos del pago.
            Licencia::create([
                'nit_empresa' => $empresa->nit,
                'id_plan' => $plan->id_plan,
                'estado' => 'pendiente',
                'valor' => $plan->precio,
                // Código del pago simulado (SIM-...) o PRUEBA-GRATIS.
                'referencia_pago' => $pago['referencia'] ?? 'PRUEBA-GRATIS',
                // Texto con marca y últimos 4 dígitos de la tarjeta (nunca la tarjeta completa).
                'observaciones' => $pago
                    ? "Pago simulado con {$pago['marca']} terminada en {$pago['ultimos4']} a nombre de {$pago['titular']}"
                    : 'Solicitud de prueba gratis desde la página de planes',
                'fecha_solicitud' => now(),
            ]);

            // 4) Crea la página pública por defecto de la óptica.
            PaginaEmpresa::deEmpresa($empresa);

            // 5) Deja constancia en la bitácora.
            Actividad::registrar($empresa->nit, 'licencia', "Se registró desde la página de planes con el plan {$plan->nombre}", 'empresa', $empresa->nit);

            // Devuelve la óptica creada.
            return $empresa;
        });
    }

    /**
     * Activa la óptica y su administrador y envía las credenciales al correo de la óptica.
     *
     * @return array{correo: string, usuario: string, password: string, enviado: bool}
     */
    // El superadmin aprueba el primer pago: activa la óptica, crea la contraseña temporal y manda las credenciales por correo.
    public static function aprobar(Licencia $licencia, ?string $referencia = null, ?float $valor = null): array
    {
        // La óptica a la que pertenece esa licencia.
        $empresa = $licencia->empresa;
        // AQUÍ se genera la contraseña aleatoria del admin: 10 caracteres, con letras y números, sin símbolos.
        $password = Str::password(10, true, true, false);

        // Todo lo que sigue se guarda junto (transacción).
        $admin = DB::transaction(function () use ($licencia, $empresa, $referencia, $valor, $password) {
            // La licencia pasa a "activa" y se calculan fecha_inicio y fecha_fin.
            Licencias::aprobar($licencia, $referencia, $valor);

            // La óptica pasa a "activa".
            $empresa->update(['estado' => 'activa']);

            // Busca al administrador (el más antiguo de esa óptica con rol admin). firstOrFail: error 404 si no existe.
            $admin = Usuario::where('nit_empresa', $empresa->nit)
                ->where('id_rol', config('visioptica.rol_admin'))
                ->orderBy('fecha_creacion')
                ->firstOrFail();

            $admin->update([
                // Guarda la contraseña ENCRIPTADA; la real solo existe en la variable $password hasta que se envía por correo.
                'password' => Hash::make($password),
                // Sigue "Pendiente" hasta que cambie la contraseña temporal en su primer ingreso.
                'id_estado' => self::USUARIO_PENDIENTE,
            ]);

            Actividad::registrar($empresa->nit, 'licencia', 'Registro aprobado: se enviaron las credenciales al correo de la óptica', 'empresa', $empresa->nit);

            // Devuelve el administrador fuera de la transacción.
            return $admin;
        });

        // El correo va por fuera de la transacción: si falla, la óptica igual queda activa
        // y el superadmin ve la contraseña en pantalla para entregarla de otra forma.
        // Va al correo de la óptica Y al del administrador (el usuario para entrar es el correo del administrador).
        // El correo va al de la óptica Y al del administrador, sin repetir (compara en minúsculas).
        $destinos = collect([$empresa->email, $admin->email])->filter()->map(fn ($c) => trim($c))
            ->unique(fn ($c) => mb_strtolower($c))->values()->all();
        // Une los correos en un texto separado por comas para mostrarlo.
        $destino = implode(', ', $destinos);
        // El correo se envía FUERA de la transacción: si falla, la óptica igual queda activa y el superadmin ve la contraseña en pantalla.
        [$enviado, $error] = self::enviarCorreos($destinos, fn () => new CredencialesAccesoMail($empresa->fresh(), $admin, $password, $licencia->fresh('plan')));

        // Devuelve el resultado al controlador (LicenciaController@aprobar) para armar el mensaje.
        return ['correo' => $destino, 'usuario' => $admin->email, 'password' => $password, 'enviado' => $enviado, 'error' => $error];
    }

    /**
     * Genera otra contraseña temporal para el administrador y la envía de nuevo al correo de la óptica
     * y al del administrador (por si el primer correo no llegó). Debe cambiarla en su próximo ingreso.
     *
     * @return array{correo: string, usuario: string, password: string, enviado: bool, error: ?string}
     */
    // Si el correo no llegó: genera OTRA contraseña temporal y la vuelve a enviar.
    public static function reenviarCredenciales(Empresa $empresa, Usuario $admin, Licencia $licencia): array
    {
        // Nueva contraseña aleatoria (misma regla: 10, letras y números).
        $password = Str::password(10, true, true, false);

        // Guarda la nueva contraseña encriptada y vuelve a ponerlo en estado pendiente (obligado a cambiarla).
        $admin->update([
            'password' => Hash::make($password),
            'id_estado' => self::USUARIO_PENDIENTE,
        ]);

        // Mismos destinatarios: óptica y administrador.
        $destinos = collect([$empresa->email, $admin->email])->filter()->map(fn ($c) => trim($c))
            ->unique(fn ($c) => mb_strtolower($c))->values()->all();

        // Envía el correo a cada destinatario.
        [$enviado, $error] = self::enviarCorreos($destinos, fn () => new CredencialesAccesoMail($empresa, $admin, $password, $licencia->loadMissing('plan')));

        // Bitácora: quedó registrado que se reenviaron credenciales.
        Actividad::registrar($empresa->nit, 'editar', 'Se generaron y reenviaron las credenciales de acceso al administrador ' . $admin->email, 'usuario', $admin->documento);

        return ['correo' => implode(', ', $destinos), 'usuario' => $admin->email, 'password' => $password, 'enviado' => $enviado, 'error' => $error];
    }

    /**
     * Envía el mismo correo a cada destinatario POR SEPARADO: si uno falla (dirección mal escrita,
     * rechazo del proveedor…) el otro igual lo recibe. Devuelve [todos_enviados, detalle_de_fallos].
     *
     * @param array<int, string> $destinos
     * @return array{0: bool, 1: ?string}
     */
    // Envía el mismo correo a cada destinatario por separado: si uno falla, el otro igual lo recibe.
    protected static function enviarCorreos(array $destinos, \Closure $mailable): array
    {
        // Aquí se acumulan los errores de envío.
        $fallos = [];

        // Recorre cada correo.
        foreach ($destinos as $destino) {
            try {
                // $mailable es una función que crea el correo; se ejecuta una vez por destinatario.
                Mail::to($destino)->send($mailable());
            } catch (\Throwable $e) {
                // Guarda el error en storage/logs.
                report($e);
                // Anota qué dirección falló y por qué.
                $fallos[] = "{$destino}: " . $e->getMessage();
            }
        }

        // Devuelve [¿todos enviados?, texto con los fallos o null].
        return [empty($fallos), $fallos ? implode(' | ', $fallos) : null];
    }

    /**
     * Borra el registro completo (óptica, administrador, licencia y página)
     * para que el NIT y el correo queden libres.
     */
    // Rechazo de un registro nuevo: borra todo para que el NIT y el correo queden libres.
    public static function rechazar(Licencia $licencia): void
    {
        $empresa = $licencia->empresa;

        // Borra en una transacción.
        DB::transaction(function () use ($empresa) {
            // Borra los usuarios de esa óptica.
            Usuario::where('nit_empresa', $empresa->nit)->delete();
            // Borra la óptica; la base de datos borra en cascada su licencia, página y actividad.
            $empresa->delete(); // licencia, página y actividad se borran en cascada
        });

        // Deja constancia del rechazo (sin NIT de óptica porque ya no existe).
        Actividad::registrar(null, 'licencia', "Registro de {$empresa->nombre} (NIT {$empresa->nit}) rechazado", 'empresa', $empresa->nit);
    }
}
