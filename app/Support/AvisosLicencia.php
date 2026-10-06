<?php

namespace App\Support;

use App\Mail\AvisoVencimientoMail;
use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Usuario;
use Illuminate\Support\Facades\Mail;

/**
 * Envía el correo de "tu licencia está por vencer".
 *
 * Cada óptica recibe dos avisos por periodo:
 *   - el primero cuando le quedan 30 días o menos,
 *   - el segundo cuando le quedan 7 días o menos.
 * La columna licencia.aviso_pago_enviado guarda cuándo se envió el último,
 * así el aviso no se repite aunque el proceso corra todos los días.
 */
class AvisosLicencia
{
    public const SEGUNDO_AVISO_DIAS = 7;

    /**
     * Revisa todas las ópticas activas y envía los avisos que falten.
     *
     * @return array{enviados: int, errores: int, detalle: array<int, string>}
     */
    public static function enviarPendientes(): array
    {
        Licencias::marcarVencidas();

        $resultado = ['enviados' => 0, 'errores' => 0, 'detalle' => []];
        $diasAviso = (int) config('visioptica.dias_aviso');

        foreach (Empresa::where('estado', 'activa')->get() as $empresa) {
            // La licencia que marca el final de su cobertura (incluye renovaciones ya aprobadas).
            $licencia = $empresa->licencias()->with('plan')
                ->where('estado', 'activa')
                ->whereDate('fecha_fin', '>=', today())
                ->orderByDesc('fecha_fin')
                ->first();

            if (! $licencia) {
                continue;
            }

            $dias = $licencia->diasRestantes();
            if ($dias > $diasAviso || ! static::tocaAviso($licencia, $dias)) {
                continue;
            }

            $destinatarios = static::destinatarios($empresa);

            try {
                Mail::to($destinatarios)->send(new AvisoVencimientoMail($empresa, $licencia, $dias));

                $licencia->update(['aviso_pago_enviado' => now()]);
                Actividad::registrar($empresa->nit, 'licencia', "Aviso de vencimiento enviado ({$dias} días restantes)", 'licencia', $licencia->id_licencia);

                $resultado['enviados']++;
                $resultado['detalle'][] = "{$empresa->nombre}: aviso enviado, le quedan {$dias} días.";
            } catch (\Throwable $e) {
                report($e);
                $resultado['errores']++;
                $resultado['detalle'][] = "{$empresa->nombre}: no se pudo enviar el correo ({$e->getMessage()}).";
            }
        }

        return $resultado;
    }

    /**
     * ¿Le toca aviso hoy?
     * - Nunca se le ha avisado en este periodo → sí.
     * - Ya se le avisó, pero antes de entrar a los últimos 7 días, y ahora le quedan 7 o menos → sí (segundo aviso).
     */
    protected static function tocaAviso(Licencia $licencia, int $dias): bool
    {
        if (! $licencia->aviso_pago_enviado) {
            return true;
        }

        $inicioSegundoAviso = $licencia->fecha_fin->copy()->subDays(self::SEGUNDO_AVISO_DIAS)->startOfDay();

        return $dias <= self::SEGUNDO_AVISO_DIAS && $licencia->aviso_pago_enviado->lt($inicioSegundoAviso);
    }

    /**
     * Correo de la óptica y de sus administradores, sin repetidos.
     */
    protected static function destinatarios(Empresa $empresa): array
    {
        return collect([$empresa->email])
            ->merge(Usuario::where('nit_empresa', $empresa->nit)
                ->where('id_rol', config('visioptica.rol_admin'))
                ->pluck('email'))
            ->filter()
            ->map(fn ($c) => mb_strtolower(trim($c)))
            ->unique()
            ->values()
            ->all();
    }
}
