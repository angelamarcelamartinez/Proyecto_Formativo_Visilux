<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Calcula los turnos libres para agendar citas a partir de la tabla
 * `horario` (bloques de atención de cada optómetra) y de las citas
 * que ya están asignadas en `asignacion_cita`.
 *
 * Cada bloque de tipo "cita" se parte en turnos de 30 minutos.
 */
class AgendaCitas
{
    public const MINUTOS_POR_CITA = 30;

    /** Días que se pueden reservar hacia adelante. */
    public const DIAS_MAXIMOS = 60;

    protected const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public static function nombreDia(Carbon $fecha): string
    {
        return self::DIAS[$fecha->dayOfWeekIso];
    }

    /** Id del estado "Cancelado" (las citas canceladas liberan el turno). */
    public static function estadoCancelado(): ?int
    {
        return DB::table('estado')->where('nom_estado', 'Cancelado')->value('id_estado');
    }

    /** Id del estado con el que nace una cita nueva ("Pendiente"). */
    public static function estadoPendiente(): int
    {
        return (int) (DB::table('estado')->where('nom_estado', 'Pendiente')->value('id_estado')
            ?? DB::table('estado')->orderBy('id_estado')->value('id_estado'));
    }

    /**
     * Turnos libres de una fecha: [['hora' => '08:00', 'id_optometra' => 1, 'optometra' => 'Nombre'], ...]
     */
    public static function turnosLibres(Carbon $fecha): Collection
    {
        $hoy = now()->startOfDay();
        if ($fecha->lt($hoy) || $fecha->gt($hoy->copy()->addDays(self::DIAS_MAXIMOS))) {
            return collect();
        }

        $dia = self::nombreDia($fecha);

        $bloques = DB::table('horario')
            ->join('optometra', 'optometra.doc_optometra', '=', 'horario.id_optometra')
            ->where('horario.dia_semana', $dia)
            ->where('horario.tipo_bloque', 'cita')
            ->where('horario.estado', 'libre')
            ->where('optometra.id_estado', 1) // solo optómetras activos
            ->orderBy('horario.hora_inicio')
            ->get([
                'horario.id_optometra', 'horario.hora_inicio', 'horario.hora_fin',
                DB::raw("CONCAT(optometra.nombre, ' ', optometra.apellido) as optometra"),
            ]);

        if ($bloques->isEmpty()) {
            return collect();
        }

        // Bloques marcados como "bloqueado" ese día (vacaciones, reuniones, etc.)
        $bloqueos = DB::table('horario')
            ->where('dia_semana', $dia)
            ->where('estado', 'bloqueado')
            ->get(['id_optometra', 'hora_inicio', 'hora_fin']);

        // Citas ya tomadas ese día (las canceladas no cuentan)
        $cancelado = self::estadoCancelado();
        $ocupadas = DB::table('asignacion_cita')
            ->whereDate('fecha_cita', $fecha->toDateString())
            ->when($cancelado, fn ($q) => $q->where('id_estado', '!=', $cancelado))
            ->get(['id_optometra', 'hora_cita'])
            ->map(fn ($c) => $c->id_optometra.'|'.substr($c->hora_cita, 0, 5));

        // Solicitudes de visitantes (sin cuenta) que siguen pendientes:
        // también apartan el turno hasta que el administrador las resuelva.
        if (self::solicitudesGuardanTurno()) {
            $pendiente = self::estadoPendiente();
            $solicitudes = DB::table('usuarios_no_registrados')
                ->whereDate('fecha_cita', $fecha->toDateString())
                ->whereNotNull('hora_cita')
                ->where(fn ($q) => $q->whereNull('id_estado')->orWhere('id_estado', $pendiente))
                ->get(['id_optometra', 'hora_cita'])
                ->map(fn ($c) => $c->id_optometra.'|'.substr($c->hora_cita, 0, 5));
            $ocupadas = $ocupadas->merge($solicitudes);
        }

        $ocupadas = $ocupadas->flip();

        $limiteHoy = $fecha->isToday() ? now()->addMinutes(30)->format('H:i') : null;

        $turnos = collect();
        foreach ($bloques as $b) {
            $inicio = Carbon::parse($fecha->toDateString().' '.$b->hora_inicio);
            $fin = Carbon::parse($fecha->toDateString().' '.$b->hora_fin);

            for ($t = $inicio->copy(); $t->copy()->addMinutes(self::MINUTOS_POR_CITA)->lte($fin); $t->addMinutes(self::MINUTOS_POR_CITA)) {
                $hora = $t->format('H:i');

                if ($limiteHoy && $hora < $limiteHoy) {
                    continue;
                }
                if ($ocupadas->has($b->id_optometra.'|'.$hora)) {
                    continue;
                }
                $bloqueado = $bloqueos->contains(fn ($x) => (int) $x->id_optometra === (int) $b->id_optometra
                    && $hora >= substr($x->hora_inicio, 0, 5) && $hora < substr($x->hora_fin, 0, 5));
                if ($bloqueado) {
                    continue;
                }

                $turnos->push([
                    'hora' => $hora,
                    'id_optometra' => (int) $b->id_optometra,
                    'optometra' => $b->optometra,
                ]);
            }
        }

        return $turnos->unique(fn ($t) => $t['id_optometra'].'|'.$t['hora'])
            ->sortBy('hora')
            ->values();
    }

    /** ¿La tabla de solicitudes ya tiene las columnas de fecha y hora? (script SQL ejecutado) */
    public static function solicitudesGuardanTurno(): bool
    {
        static $tiene = null;

        return $tiene ??= Schema::hasColumns('usuarios_no_registrados', ['fecha_cita', 'hora_cita', 'id_optometra', 'id_estado']);
    }

    /**
     * ¿Ese turno ya está ocupado por una cita (no cancelada)?
     * Se usa también al crear/editar citas desde el panel.
     */
    public static function citaOcupada(string $fecha, string $hora, int $idOptometra, ?int $ignorarIdCita = null): bool
    {
        $cancelado = self::estadoCancelado();

        return DB::table('asignacion_cita')
            ->whereDate('fecha_cita', $fecha)
            ->where('hora_cita', substr($hora, 0, 5).':00')
            ->where('id_optometra', $idOptometra)
            ->when($cancelado, fn ($q) => $q->where('id_estado', '!=', $cancelado))
            ->when($ignorarIdCita, fn ($q) => $q->where('id_cita', '!=', $ignorarIdCita))
            ->exists();
    }

    public static function turnoDisponible(Carbon $fecha, string $hora, int $idOptometra): bool
    {
        return self::turnosLibres($fecha)
            ->contains(fn ($t) => $t['hora'] === $hora && $t['id_optometra'] === $idOptometra);
    }

    /** Días de la semana en los que hay atención de citas (para orientar al paciente). */
    public static function diasConAtencion(): array
    {
        $dias = DB::table('horario')
            ->join('optometra', 'optometra.doc_optometra', '=', 'horario.id_optometra')
            ->where('horario.tipo_bloque', 'cita')
            ->where('horario.estado', 'libre')
            ->where('optometra.id_estado', 1)
            ->distinct()
            ->pluck('horario.dia_semana')
            ->all();

        return array_values(array_filter(self::DIAS, fn ($d) => in_array($d, $dias, true)));
    }
}
