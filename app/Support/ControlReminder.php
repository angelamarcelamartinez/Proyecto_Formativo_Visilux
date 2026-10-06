<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Un año después de la última cita de un paciente, conviene recordarle
 * que agende una cita de control para revisar el progreso de su visión.
 * Esta clase calcula qué pacientes están próximos a cumplir ese año.
 */
class ControlReminder
{
    /**
     * Pacientes cuya última cita está por cumplir un año dentro de la
     * ventana de días indicada (por defecto, el próximo mes).
     */
    public static function proximos(int $diasVentana = 30): Collection
    {
        $hoy = Carbon::today();
        $limite = $hoy->copy()->addDays($diasVentana);

        $ultimasCitas = DB::table('asignacion_cita')
            ->select('id_usuario', DB::raw('MAX(fecha_cita) as ultima_cita'))
            ->groupBy('id_usuario')
            ->get()
            ->keyBy('id_usuario');

        if ($ultimasCitas->isEmpty()) {
            return collect();
        }

        $usuarios = DB::table('usuario')
            ->whereIn('documento', $ultimasCitas->keys())
            ->get()
            ->keyBy('documento');

        $resultado = collect();

        foreach ($ultimasCitas as $documento => $fila) {
            $ultima = Carbon::parse($fila->ultima_cita);
            $aniversario = $ultima->copy()->addYear();

            if (! $aniversario->between($hoy, $limite)) {
                continue;
            }

            $usuario = $usuarios->get($documento);
            if (! $usuario) {
                continue;
            }

            $resultado->push((object) [
                'documento' => $documento,
                'nombres' => $usuario->nombres,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->email,
                'telefono' => $usuario->telefono,
                'ultima_cita' => $ultima,
                'fecha_aniversario' => $aniversario,
                'dias_restantes' => (int) $hoy->diffInDays($aniversario, false),
            ]);
        }

        return $resultado->sortBy('fecha_aniversario')->values();
    }
}
