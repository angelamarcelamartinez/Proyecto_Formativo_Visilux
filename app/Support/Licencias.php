<?php

namespace App\Support;

use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Todas las reglas de negocio de las licencias en un solo lugar.
 */
class Licencias
{
    /**
     * La óptica pide un plan. Queda "pendiente" hasta que el superadmin confirme el pago.
     */
    public static function solicitar(Empresa $empresa, Plan $plan): Licencia
    {
        if (! $plan->activo) {
            throw ValidationException::withMessages(['id_plan' => 'Ese plan ya no está disponible.']);
        }

        if ($plan->es_prueba && $empresa->prueba_usada) {
            throw ValidationException::withMessages(['id_plan' => 'La prueba gratis solo se puede usar una vez.']);
        }

        $yaPendiente = $empresa->licencias()->where('estado', 'pendiente')->exists();
        if ($yaPendiente) {
            throw ValidationException::withMessages(['id_plan' => 'Ya tienes una solicitud pendiente. Espera a que la aprobemos o cancélala.']);
        }

        $licencia = Licencia::create([
            'nit_empresa' => $empresa->nit,
            'id_plan' => $plan->id_plan,
            'estado' => 'pendiente',
            'valor' => $plan->precio,
            'fecha_solicitud' => now(),
        ]);

        Actividad::registrar($empresa->nit, 'licencia', "Solicitó el plan {$plan->nombre}", 'licencia', $licencia->id_licencia);

        return $licencia;
    }

    /**
     * Activa una licencia y calcula sus fechas:
     * - si la óptica todavía tiene días, la nueva empieza cuando termina la actual;
     * - si ya venció, empieza hoy.
     */
    public static function aprobar(Licencia $licencia, ?string $referencia = null, ?float $valor = null): Licencia
    {
        if ($licencia->estado !== 'pendiente') {
            throw ValidationException::withMessages(['general' => 'Solo se pueden aprobar solicitudes pendientes.']);
        }

        return DB::transaction(function () use ($licencia, $referencia, $valor) {
            $empresa = $licencia->empresa;
            $plan = $licencia->plan;

            $inicio = static::fechaInicioPara($empresa);

            $licencia->update([
                'estado' => 'activa',
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $inicio->copy()->addMonthsNoOverflow($plan->meses)->toDateString(),
                'referencia_pago' => $referencia ?: $licencia->referencia_pago,
                'valor' => $valor ?? $licencia->valor,
                'fecha_aprobacion' => now(),
            ]);

            if ($plan->es_prueba && ! $empresa->prueba_usada) {
                $empresa->update(['prueba_usada' => 1]);
            }

            // La licencia vencida que tenían pasa a «inactiva»: queda en el historial, pero ya no cuenta.
            // (Si todavía tenía días, sigue activa hasta que termine; luego marcarVencidas() la inactiva.)
            Licencia::where('nit_empresa', $empresa->nit)
                ->where('estado', 'vencida')
                ->where('id_licencia', '<>', $licencia->id_licencia)
                ->update(['estado' => 'inactiva']);

            Actividad::registrar(
                $empresa->nit, 'licencia',
                "Licencia {$plan->nombre} activada hasta " . $licencia->fecha_fin->format('d/m/Y'),
                'licencia', $licencia->id_licencia
            );

            return $licencia->fresh(['plan']);
        });
    }

    /**
     * Renovación pagada con tarjeta (pago simulado): queda «pendiente» con su propio id
     * hasta que el superadmin la acepte. No se vuelve a verificar NIT ni Cámara de Comercio.
     *
     * @param array{referencia: string, marca: string, ultimos4: string, titular: string} $pago
     */
    public static function renovar(Empresa $empresa, Plan $plan, array $pago): Licencia
    {
        $licencia = Licencia::create([
            'nit_empresa' => $empresa->nit,
            'id_plan' => $plan->id_plan,
            'estado' => 'pendiente',
            'valor' => $plan->precio,
            'referencia_pago' => $pago['referencia'],
            'observaciones' => "Renovación con pago simulado: {$pago['marca']} terminada en {$pago['ultimos4']} a nombre de {$pago['titular']}",
            'fecha_solicitud' => now(),
        ]);

        Actividad::registrar($empresa->nit, 'licencia', "Pagó la renovación del plan {$plan->nombre}", 'licencia', $licencia->id_licencia);

        return $licencia;
    }

    /**
     * El superadmin asigna un plan directamente (sin que la óptica lo haya pedido).
     */
    public static function asignar(Empresa $empresa, Plan $plan, ?string $referencia, ?float $valor, ?string $observaciones = null): Licencia
    {
        if ($plan->es_prueba && $empresa->prueba_usada) {
            throw ValidationException::withMessages(['id_plan' => 'Esta óptica ya usó la prueba gratis.']);
        }

        $licencia = Licencia::create([
            'nit_empresa' => $empresa->nit,
            'id_plan' => $plan->id_plan,
            'estado' => 'pendiente',
            'valor' => $valor ?? $plan->precio,
            'referencia_pago' => $referencia,
            'observaciones' => $observaciones,
            'fecha_solicitud' => now(),
        ]);

        return static::aprobar($licencia, $referencia, $valor);
    }

    public static function rechazar(Licencia $licencia, ?string $motivo = null): void
    {
        if ($licencia->estado !== 'pendiente') {
            throw ValidationException::withMessages(['general' => 'Solo se pueden rechazar solicitudes pendientes.']);
        }

        $licencia->update([
            'estado' => 'cancelada',
            'observaciones' => $motivo ?: $licencia->observaciones,
        ]);

        Actividad::registrar($licencia->nit_empresa, 'licencia', 'Solicitud de ' . ($licencia->plan->nombre ?? 'plan') . ' rechazada', 'licencia', $licencia->id_licencia);
    }

    /**
     * Día en que debe empezar una licencia nueva para esta óptica.
     */
    public static function fechaInicioPara(Empresa $empresa): Carbon
    {
        $fin = $empresa->coberturaHasta();

        return ($fin && $fin->gte(today())) ? $fin->copy() : today();
    }

    /**
     * Pasa a "vencida" las licencias activas cuya fecha_fin ya pasó.
     * Se llama al abrir el panel del superadmin, así no depende de un cron.
     */
    public static function marcarVencidas(): int
    {
        $n = Licencia::where('estado', 'activa')
            ->whereDate('fecha_fin', '<', today())
            ->update(['estado' => 'vencida']);

        // Una licencia vencida de una óptica que ya tiene otra vigente pasa a «inactiva».
        $conVigente = Licencia::where('estado', 'activa')
            ->whereDate('fecha_fin', '>=', today())
            ->pluck('nit_empresa')->unique()->all();

        if ($conVigente) {
            Licencia::where('estado', 'vencida')->whereIn('nit_empresa', $conVigente)->update(['estado' => 'inactiva']);
        }

        return $n;
    }

    /**
     * Lo mismo que marcarVencidas(), pero como mucho una vez cada 5 minutos: se llama desde un
     * middleware en cada petición para que los estados se actualicen solos con la hora del sistema,
     * sin depender del cron del hosting.
     */
    public static function marcarVencidasAutomatico(): void
    {
        try {
            if (\Illuminate\Support\Facades\Cache::add('licencias:marcar_vencidas', 1, 300)) {
                static::marcarVencidas();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Correos a los que se avisa de algo de la óptica: el de la empresa y el de sus administradores.
     *
     * @return array<int, string>
     */
    public static function correosDe(Empresa $empresa): array
    {
        return collect([$empresa->email])
            ->merge($empresa->usuarios()->where('id_rol', config('visioptica.rol_admin'))->pluck('email'))
            ->filter()->map(fn ($c) => trim($c))->unique(fn ($c) => mb_strtolower($c))->values()->all();
    }

    /**
     * El administrador puede entrar al panel aunque la licencia haya vencido (verá la pantalla de
     * renovación). No puede si la óptica no existe, está suspendida o espera su primer pago.
     */
    public static function adminPuedeEntrar(?Empresa $empresa): bool
    {
        return $empresa && ! $empresa->estaSuspendida() && ! $empresa->estaPendiente();
    }

    /**
     * Confirma por correo (a la óptica y al administrador) que se recibió el pago de la renovación.
     */
    public static function notificarPagoRecibido(Licencia $licencia): bool
    {
        try {
            Mail::to(static::correosDe($licencia->empresa))->send(new \App\Mail\RenovacionRecibidaMail($licencia->empresa, $licencia->loadMissing('plan')));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Avisa por correo a la óptica y a sus administradores que el plan quedó activo.
     * Devuelve false si el correo no se pudo enviar (la licencia igual queda activa).
     */
    public static function notificarActivacion(Licencia $licencia): bool
    {
        $empresa = $licencia->empresa;

        $correos = static::correosDe($empresa);

        try {
            Mail::to($correos)->send(new \App\Mail\RenovacionAprobadaMail($empresa, $licencia->loadMissing('plan')));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * ¿Puede la óptica usar el panel hoy?
     */
    public static function puedeIngresar(?Empresa $empresa): bool
    {
        return $empresa && ! $empresa->estaSuspendida() && $empresa->licenciaVigente() !== null;
    }
}
