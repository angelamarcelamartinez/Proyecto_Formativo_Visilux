<?php

namespace App\Support;

use App\Mail\CredencialesAccesoMail;
use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\PaginaEmpresa;
use App\Models\Plan;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
class RegistroOptica
{
    // id_estado de la tabla `estado`
    public const USUARIO_ACTIVO = 1;
    public const USUARIO_PENDIENTE = 3;

    /**
     * @param array $datos Datos validados del formulario (óptica y administrador).
     * @param array|null $pago Resultado de PagoSimulado::cobrar(), o null si el plan es gratis.
     */
    public static function registrar(array $datos, Plan $plan, ?array $pago, ?string $rutaCamara = null): Empresa
    {
        return DB::transaction(function () use ($datos, $plan, $pago, $rutaCamara) {
            $empresa = Empresa::create([
                'nit' => $datos['nit'],
                'nombre' => $datos['nombre'],
                'slug' => Empresa::slugDisponible($datos['nombre']),
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'direccion' => $datos['direccion'] ?? null,
                'ciudad' => $datos['ciudad'],
                'estado' => 'pendiente',
                'prueba_usada' => 0,
                'fecha_registro' => now(),
                // PDF de la Cámara de Comercio: el superadmin lo revisa y confirma el NIT al aprobar.
                'camara_comercio' => $rutaCamara,
            ]);

            // El administrador queda creado pero sin poder entrar: su contraseña es
            // aleatoria y nadie la conoce. La real se genera al aprobar.
            Usuario::create([
                'documento' => $datos['admin_documento'],
                'nombres' => $datos['admin_nombres'],
                'apellido' => $datos['admin_apellido'] ?? '',
                'telefono' => $datos['admin_telefono'],
                'email' => $datos['admin_email'],
                'password' => Hash::make(Str::random(40)),
                'fecha_creacion' => now(),
                'id_rol' => config('visioptica.rol_admin'),
                'id_tipo_docu' => 1,
                'id_estado' => self::USUARIO_PENDIENTE,
                'nit_empresa' => $empresa->nit,
            ]);

            Licencia::create([
                'nit_empresa' => $empresa->nit,
                'id_plan' => $plan->id_plan,
                'estado' => 'pendiente',
                'valor' => $plan->precio,
                'referencia_pago' => $pago['referencia'] ?? 'PRUEBA-GRATIS',
                'observaciones' => $pago
                    ? "Pago simulado con {$pago['marca']} terminada en {$pago['ultimos4']} a nombre de {$pago['titular']}"
                    : 'Solicitud de prueba gratis desde la página de planes',
                'fecha_solicitud' => now(),
            ]);

            PaginaEmpresa::deEmpresa($empresa);

            Actividad::registrar($empresa->nit, 'licencia', "Se registró desde la página de planes con el plan {$plan->nombre}", 'empresa', $empresa->nit);

            return $empresa;
        });
    }

    /**
     * Activa la óptica y su administrador y envía las credenciales al correo de la óptica.
     *
     * @return array{correo: string, usuario: string, password: string, enviado: bool}
     */
    public static function aprobar(Licencia $licencia, ?string $referencia = null, ?float $valor = null): array
    {
        $empresa = $licencia->empresa;
        $password = Str::password(10, true, true, false);

        $admin = DB::transaction(function () use ($licencia, $empresa, $referencia, $valor, $password) {
            Licencias::aprobar($licencia, $referencia, $valor);

            $empresa->update(['estado' => 'activa']);

            $admin = Usuario::where('nit_empresa', $empresa->nit)
                ->where('id_rol', config('visioptica.rol_admin'))
                ->orderBy('fecha_creacion')
                ->firstOrFail();

            $admin->update([
                'password' => Hash::make($password),
                // Sigue "Pendiente" hasta que cambie la contraseña temporal en su primer ingreso.
                'id_estado' => self::USUARIO_PENDIENTE,
            ]);

            Actividad::registrar($empresa->nit, 'licencia', 'Registro aprobado: se enviaron las credenciales al correo de la óptica', 'empresa', $empresa->nit);

            return $admin;
        });

        // El correo va por fuera de la transacción: si falla, la óptica igual queda activa
        // y el superadmin ve la contraseña en pantalla para entregarla de otra forma.
        // Va al correo de la óptica; el usuario para entrar es el correo del administrador.
        $destino = $empresa->email ?: $admin->email;
        $enviado = true;
        try {
            Mail::to($destino)->send(new CredencialesAccesoMail($empresa->fresh(), $admin, $password, $licencia->fresh('plan')));
        } catch (\Throwable $e) {
            report($e);
            $enviado = false;
        }

        return ['correo' => $destino, 'usuario' => $admin->email, 'password' => $password, 'enviado' => $enviado];
    }

    /**
     * Borra el registro completo (óptica, administrador, licencia y página)
     * para que el NIT y el correo queden libres.
     */
    public static function rechazar(Licencia $licencia): void
    {
        $empresa = $licencia->empresa;

        DB::transaction(function () use ($empresa) {
            Usuario::where('nit_empresa', $empresa->nit)->delete();
            $empresa->delete(); // licencia, página y actividad se borran en cascada
        });

        Actividad::registrar(null, 'licencia', "Registro de {$empresa->nombre} (NIT {$empresa->nit}) rechazado", 'empresa', $empresa->nit);
    }
}
