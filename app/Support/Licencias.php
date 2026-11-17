<?php

// Esta clase vive en la carpeta app/Support, por eso su "namespace" (su dirección) es App\Support.
// El namespace evita que dos clases con el mismo nombre se confundan entre sí.
namespace App\Support;

// "use" = importar. Así abajo escribimos "Empresa" en vez de "App\Models\Empresa".
use App\Models\Actividad;       // Modelo de la tabla "actividad": la bitácora (quién hizo qué).
use App\Models\Empresa;         // Modelo de la tabla "empresa": cada óptica.
use App\Models\Licencia;        // Modelo de la tabla "licencia": cada licencia comprada.
use App\Models\Plan;            // Modelo de la tabla "plan": los planes que se venden (1 mes, 6 meses...).
use Illuminate\Support\Carbon;  // Clase de Laravel para trabajar con fechas (sumar meses, comparar días).
use Illuminate\Support\Facades\DB;   // Para usar transacciones de base de datos.
use Illuminate\Support\Facades\Mail; // Para enviar correos.
use Illuminate\Validation\ValidationException; // Error especial que devuelve el mensaje al formulario.

/**
 * Todas las reglas de negocio de las licencias en un solo lugar.
 */
// Clase "Licencias": no se crean objetos con ella, solo se llaman sus funciones estáticas,
// por ejemplo Licencias::aprobar(...). Por eso todas las funciones dicen "static".
class Licencias
{
    /**
     * La óptica pide un plan. Queda "pendiente" hasta que el superadmin confirme el pago.
     */
    // Recibe la óptica que pide y el plan elegido. Devuelve la licencia creada.
    public static function solicitar(Empresa $empresa, Plan $plan): Licencia
    {
        // Si el plan fue desactivado por el superadmin, ya no se puede pedir.
        if (! $plan->activo) {
            // "throw" corta la función y devuelve el error al formulario, pegado al campo "id_plan".
            throw ValidationException::withMessages(['id_plan' => 'Ese plan ya no está disponible.']);
        }

        // La prueba gratis es de una sola vez: si el plan es de prueba y la óptica ya la usó, error.
        if ($plan->es_prueba && $empresa->prueba_usada) {
            throw ValidationException::withMessages(['id_plan' => 'La prueba gratis solo se puede usar una vez.']);
        }

        // Busca en las licencias de esta óptica si ya hay una en estado "pendiente".
        // exists() devuelve true o false, sin traer los datos.
        $yaPendiente = $empresa->licencias()->where('estado', 'pendiente')->exists();
        // Solo se permite una solicitud pendiente a la vez, para no duplicar pagos.
        if ($yaPendiente) {
            throw ValidationException::withMessages(['id_plan' => 'Ya tienes una solicitud pendiente. Espera a que la aprobemos o cancélala.']);
        }

        // Si pasó todas las validaciones, crea la fila nueva en la tabla "licencia".
        $licencia = Licencia::create([
            'nit_empresa' => $empresa->nit,       // De qué óptica es.
            'id_plan' => $plan->id_plan,          // Qué plan pidió.
            'estado' => 'pendiente',              // Nace pendiente: aún no tiene fechas ni acceso.
            'valor' => $plan->precio,             // Se guarda el precio de ese momento (por si el plan cambia después).
            'fecha_solicitud' => now(),           // Fecha y hora actual.
        ]);

        // Deja constancia en la bitácora. Parámetros: NIT, tipo, descripción, tabla afectada, id del registro.
        Actividad::registrar($empresa->nit, 'licencia', "Solicitó el plan {$plan->nombre}", 'licencia', $licencia->id_licencia);

        // Devuelve la licencia recién creada a quien llamó la función.
        return $licencia;
    }

    /**
     * Activa una licencia y calcula sus fechas:
     * - si la óptica todavía tiene días, la nueva empieza cuando termina la actual;
     * - si ya venció, empieza hoy.
     */
    // Aprueba una licencia pendiente. "?string" y "?float" significan "puede venir vacío (null)".
    public static function aprobar(Licencia $licencia, ?string $referencia = null, ?float $valor = null): Licencia
    {
        // Solo se pueden aprobar licencias pendientes. Evita aprobar dos veces la misma.
        if ($licencia->estado !== 'pendiente') {
            throw ValidationException::withMessages(['general' => 'Solo se pueden aprobar solicitudes pendientes.']);
        }

        // Transacción: todos los cambios de adentro se guardan juntos, o si algo falla no se guarda ninguno.
        // "use (...)" le pasa a la función interna las variables de afuera.
        return DB::transaction(function () use ($licencia, $referencia, $valor) {
            $empresa = $licencia->empresa; // La óptica dueña de la licencia (relación del modelo).
            $plan = $licencia->plan;       // El plan de la licencia (relación del modelo).

            // Calcula desde qué día empieza (ver fechaInicioPara más abajo).
            $inicio = static::fechaInicioPara($empresa);

            // Actualiza la misma fila de la licencia.
            $licencia->update([
                'estado' => 'activa',                                  // Ya tiene vigencia.
                'fecha_inicio' => $inicio->toDateString(),             // Fecha de inicio como texto "2026-10-18".
                // copy() hace una copia para no modificar $inicio. Suma los meses del plan.
                // addMonthsNoOverflow evita errores: 31 enero + 1 mes = 28 febrero, no 3 de marzo.
                'fecha_fin' => $inicio->copy()->addMonthsNoOverflow($plan->meses)->toDateString(),
                // "?:" = si $referencia viene vacía, deja la que ya tenía la licencia.
                'referencia_pago' => $referencia ?: $licencia->referencia_pago,
                // "??" = si $valor es null, deja el valor que ya tenía.
                'valor' => $valor ?? $licencia->valor,
                'fecha_aprobacion' => now(),                           // Cuándo la aprobó el superadmin.
            ]);

            // Si era plan de prueba y la óptica aún no tenía la prueba marcada como usada...
            if ($plan->es_prueba && ! $empresa->prueba_usada) {
                $empresa->update(['prueba_usada' => 1]); // ...se marca, para que no la repita.
            }

            // La licencia vencida que tenían pasa a «inactiva»: queda en el historial, pero ya no cuenta.
            // (Si todavía tenía días, sigue activa hasta que termine; luego marcarVencidas() la inactiva.)
            Licencia::where('nit_empresa', $empresa->nit)           // Licencias de esta óptica...
                ->where('estado', 'vencida')                        // ...que estén vencidas...
                ->where('id_licencia', '<>', $licencia->id_licencia) // ...y que no sean la que acabamos de activar ("<>" = distinto)...
                ->update(['estado' => 'inactiva']);                 // ...pasan a inactiva (historial).

            // Bitácora: deja escrito hasta qué fecha quedó activa.
            Actividad::registrar(
                $empresa->nit, 'licencia',
                "Licencia {$plan->nombre} activada hasta " . $licencia->fecha_fin->format('d/m/Y'), // "." une textos.
                'licencia', $licencia->id_licencia
            );

            // fresh() vuelve a leer la licencia de la base de datos con el plan cargado, ya con los cambios.
            return $licencia->fresh(['plan']);
        });
    }

    /**
     * Renovación pagada con tarjeta (pago simulado): queda «pendiente» con su propio id
     * hasta que el superadmin la acepte. No se vuelve a verificar NIT ni Cámara de Comercio.
     *
     * @param array{referencia: string, marca: string, ultimos4: string, titular: string} $pago
     */
    // $pago es el arreglo que devolvió PagoSimulado::cobrar(): referencia, marca, últimos 4 y titular.
    public static function renovar(Empresa $empresa, Plan $plan, array $pago): Licencia
    {
        // Crea una licencia NUEVA (no modifica la vencida): así el historial queda completo.
        $licencia = Licencia::create([
            'nit_empresa' => $empresa->nit,
            'id_plan' => $plan->id_plan,
            'estado' => 'pendiente',                     // Espera a que el superadmin la apruebe.
            'valor' => $plan->precio,
            'referencia_pago' => $pago['referencia'],    // Código del pago simulado, ej. SIM-261018-AB12CD.
            // Texto que explica el pago. Nunca se guarda la tarjeta completa, solo marca y últimos 4 dígitos.
            'observaciones' => "Renovación con pago simulado: {$pago['marca']} terminada en {$pago['ultimos4']} a nombre de {$pago['titular']}",
            'fecha_solicitud' => now(),
        ]);

        // Bitácora del pago.
        Actividad::registrar($empresa->nit, 'licencia', "Pagó la renovación del plan {$plan->nombre}", 'licencia', $licencia->id_licencia);

        return $licencia;
    }

    /**
     * El superadmin asigna un plan directamente (sin que la óptica lo haya pedido).
     */
    // Se usa cuando el superadmin le regala o asigna un plan a una óptica desde su panel.
    public static function asignar(Empresa $empresa, Plan $plan, ?string $referencia, ?float $valor, ?string $observaciones = null): Licencia
    {
        // Misma regla: la prueba gratis solo una vez.
        if ($plan->es_prueba && $empresa->prueba_usada) {
            throw ValidationException::withMessages(['id_plan' => 'Esta óptica ya usó la prueba gratis.']);
        }

        // Crea la licencia pendiente con los datos que dio el superadmin.
        $licencia = Licencia::create([
            'nit_empresa' => $empresa->nit,
            'id_plan' => $plan->id_plan,
            'estado' => 'pendiente',
            'valor' => $valor ?? $plan->precio,   // Si no escribió valor, usa el precio del plan.
            'referencia_pago' => $referencia,
            'observaciones' => $observaciones,
            'fecha_solicitud' => now(),
        ]);

        // Como la asigna el propio superadmin, no espera: la aprueba de una vez reutilizando aprobar().
        return static::aprobar($licencia, $referencia, $valor);
    }

    // Rechaza una solicitud de pago. $motivo es opcional.
    public static function rechazar(Licencia $licencia, ?string $motivo = null): void
    {
        // Solo se rechaza lo que está pendiente.
        if ($licencia->estado !== 'pendiente') {
            throw ValidationException::withMessages(['general' => 'Solo se pueden rechazar solicitudes pendientes.']);
        }

        // No se borra: queda como "cancelada" para conservar el historial.
        $licencia->update([
            'estado' => 'cancelada',
            // Si el superadmin escribió un motivo lo guarda; si no, deja lo que ya había.
            'observaciones' => $motivo ?: $licencia->observaciones,
        ]);

        // Bitácora. "??" pone 'plan' si por alguna razón no se pudo leer el nombre del plan.
        Actividad::registrar($licencia->nit_empresa, 'licencia', 'Solicitud de ' . ($licencia->plan->nombre ?? 'plan') . ' rechazada', 'licencia', $licencia->id_licencia);
    }

    /**
     * Día en que debe empezar una licencia nueva para esta óptica.
     */
    // Devuelve un objeto Carbon (una fecha).
    public static function fechaInicioPara(Empresa $empresa): Carbon
    {
        // Hasta qué fecha llega la licencia activa actual (null si no tiene ninguna vigente).
        $fin = $empresa->coberturaHasta();

        // Si todavía le quedan días ($fin existe y es hoy o futuro), la nueva empieza cuando termina esa:
        // así no pierde los días que ya pagó. Si no, empieza hoy.
        // "gte" = greater than or equal (mayor o igual). "? :" es un if/else en una línea.
        return ($fin && $fin->gte(today())) ? $fin->copy() : today();
    }

    /**
     * Pasa a "vencida" las licencias activas cuya fecha_fin ya pasó.
     * Se llama al abrir el panel del superadmin, así no depende de un cron.
     */
    // Devuelve cuántas licencias pasaron a vencida.
    public static function marcarVencidas(): int
    {
        // Toma las licencias "activa" cuya fecha_fin es anterior a hoy y las pasa a "vencida" de un solo UPDATE.
        // update() devuelve cuántas filas cambió; eso queda en $n.
        $n = Licencia::where('estado', 'activa')
            ->whereDate('fecha_fin', '<', today())
            ->update(['estado' => 'vencida']);

        // Una licencia vencida de una óptica que ya tiene otra vigente pasa a «inactiva».
        // Primero busca los NIT de las ópticas que tienen una licencia activa y vigente (fecha_fin hoy o futuro).
        $conVigente = Licencia::where('estado', 'activa')
            ->whereDate('fecha_fin', '>=', today())
            ->pluck('nit_empresa')  // pluck = trae solo la columna nit_empresa.
            ->unique()              // Sin repetidos.
            ->all();                // Lo convierte en un arreglo normal de PHP.

        // Si hay al menos una óptica con licencia vigente...
        if ($conVigente) {
            // ...sus licencias vencidas pasan a inactiva (ya tienen otra que las reemplaza).
            Licencia::where('estado', 'vencida')->whereIn('nit_empresa', $conVigente)->update(['estado' => 'inactiva']);
        }

        return $n; // Cantidad que pasó a vencida.
    }

    /**
     * Lo mismo que marcarVencidas(), pero como mucho una vez cada 5 minutos: se llama desde un
     * middleware en cada petición para que los estados se actualicen solos con la hora del sistema,
     * sin depender del cron del hosting.
     */
    public static function marcarVencidasAutomatico(): void
    {
        // try/catch: si algo falla (por ejemplo la base de datos), el error NO tumba la página.
        try {
            // Cache::add guarda la clave SOLO si no existe y devuelve true; si ya existía devuelve false.
            // La clave dura 300 segundos (5 minutos). Resultado: la revisión corre como máximo
            // una vez cada 5 minutos aunque lleguen miles de peticiones.
            if (\Illuminate\Support\Facades\Cache::add('licencias:marcar_vencidas', 1, 300)) {
                static::marcarVencidas(); // Hace la revisión real.
            }
        } catch (\Throwable $e) {
            // Registra el error en storage/logs para revisarlo después, pero deja seguir al usuario.
            report($e);
        }
    }

    /**
     * Correos a los que se avisa de algo de la óptica: el de la empresa y el de sus administradores.
     *
     * @return array<int, string>
     */
    // Devuelve una lista de correos sin repetir.
    public static function correosDe(Empresa $empresa): array
    {
        // collect() crea una "colección" (lista con funciones útiles). Empieza con el correo de la empresa.
        return collect([$empresa->email])
            // merge = le suma los correos de los usuarios de esa óptica que tengan rol admin (id_rol 1).
            ->merge($empresa->usuarios()->where('id_rol', config('visioptica.rol_admin'))->pluck('email'))
            // filter() quita los vacíos; map() le quita espacios a cada correo;
            // unique() quita repetidos ignorando mayúsculas (mb_strtolower los pasa a minúscula para comparar);
            // values() reordena los índices; all() lo devuelve como arreglo.
            ->filter()->map(fn ($c) => trim($c))->unique(fn ($c) => mb_strtolower($c))->values()->all();
    }

    /**
     * El administrador puede entrar al panel aunque la licencia haya vencido (verá la pantalla de
     * renovación). No puede si la óptica no existe, está suspendida o espera su primer pago.
     */
    // Devuelve true o false: ¿el admin puede estar dentro del sistema?
    public static function adminPuedeEntrar(?Empresa $empresa): bool
    {
        // Debe existir la empresa Y no estar suspendida Y no estar pendiente (primer pago sin aprobar).
        // Fíjate: NO revisa si la licencia venció; por eso el admin con licencia vencida sí entra.
        return $empresa && ! $empresa->estaSuspendida() && ! $empresa->estaPendiente();
    }

    /**
     * Confirma por correo (a la óptica y al administrador) que se recibió el pago de la renovación.
     */
    // Devuelve true si el correo salió, false si falló.
    public static function notificarPagoRecibido(Licencia $licencia): bool
    {
        try {
            // Mail::to(lista de correos)->send(correo). Aquí el correo es RenovacionRecibidaMail.
            // loadMissing('plan') carga el plan solo si todavía no estaba cargado, para mostrarlo en el correo.
            Mail::to(static::correosDe($licencia->empresa))->send(new \App\Mail\RenovacionRecibidaMail($licencia->empresa, $licencia->loadMissing('plan')));

            return true; // Salió bien.
        } catch (\Throwable $e) {
            report($e); // Guarda el error en el log.

            return false; // Falló, pero el pago ya quedó registrado; el controlador solo cambia el mensaje.
        }
    }

    /**
     * Avisa por correo a la óptica y a sus administradores que el plan quedó activo.
     * Devuelve false si el correo no se pudo enviar (la licencia igual queda activa).
     */
    public static function notificarActivacion(Licencia $licencia): bool
    {
        $empresa = $licencia->empresa; // La óptica de la licencia.

        $correos = static::correosDe($empresa); // Lista de destinatarios.

        try {
            // Envía RenovacionAprobadaMail: "tu plan ya está activo".
            Mail::to($correos)->send(new \App\Mail\RenovacionAprobadaMail($empresa, $licencia->loadMissing('plan')));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false; // La licencia igual queda activa aunque el correo falle.
        }
    }

    /**
     * ¿Puede la óptica usar el panel hoy?
     */
    // La usan los middlewares y el login para decidir si dejan pasar.
    public static function puedeIngresar(?Empresa $empresa): bool
    {
        // Debe existir, no estar suspendida y tener una licencia vigente (licenciaVigente() devuelve null si no hay).
        // "!== null" = "es distinto de nada".
        return $empresa && ! $empresa->estaSuspendida() && $empresa->licenciaVigente() !== null;
    }
}
