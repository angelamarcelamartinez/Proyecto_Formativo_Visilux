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
 * Cada bloque de tipo "cita" se parte en turnos de 30 minutos. Las citas
 * de terapia (el nombre del tipo de cita contiene "terapia") duran 1 hora.
 */
class AgendaCitas
{
    public const MINUTOS_POR_CITA = 30;

    /** Duración de una cita de terapia. */
    public const MINUTOS_TERAPIA = 60;

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

    /** Minutos que dura una cita según el NOMBRE de su tipo ("terapia" = 1 hora). */
    public static function minutosPorNombre(?string $nombreTipo): int
    {
        return $nombreTipo !== null && mb_stripos($nombreTipo, 'terapia') !== false
            ? self::MINUTOS_TERAPIA
            : self::MINUTOS_POR_CITA;
    }

    /** Minutos que dura una cita según el id de su tipo (sin tipo = cita normal). */
    public static function minutosDelTipo(?int $idTipoCita): int
    {
        if (! $idTipoCita) {
            return self::MINUTOS_POR_CITA;
        }

        return self::minutosPorNombre(DB::table('tipo_cita')->where('id_tipo_cita', $idTipoCita)->value('nombre_tipo'));
    }

    /**
     * Limita una consulta que ya une la tabla `optometra` a los optómetras de la óptica $nit
     * (se comparan los 9 primeros dígitos del NIT, como en el resto del panel). Sin NIT no filtra.
     */
    protected static function deLaOptica($query, ?string $nit)
    {
        $base = substr(preg_replace('/\D/', '', (string) $nit), 0, 9);

        return $base === '' ? $query : $query->whereRaw('SUBSTR(CAST(optometra.nit_empresa AS CHAR), 1, 9) = ?', [$base]);
    }

    protected static function aMinutos(string $hora): int
    {
        return ((int) substr($hora, 0, 2)) * 60 + (int) substr($hora, 3, 2);
    }

    /** ¿[a1,a2) y [b1,b2) se pisan? */
    protected static function seCruzan(int $a1, int $a2, int $b1, int $b2): bool
    {
        return $a1 < $b2 && $b1 < $a2;
    }

    public static function nombreDia(Carbon $fecha): string
    {
        return self::DIAS[$fecha->dayOfWeekIso];
    }

    /** Id del estado "Cancelado" (las citas canceladas liberan el turno). */
    public static function estadoCancelado(): ?int
    {
        return DB::table('estado')->where('nom_estado', 'Cancelado')->value('id_estado');
    }

    /** Id del estado "Confirmada" (11 si la tabla estado no lo trae con ese nombre). */
    public static function estadoConfirmada(): int
    {
        return (int) (DB::table('estado')->where('nom_estado', 'Confirmada')->value('id_estado') ?? 11);
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
    public static function turnosLibres(Carbon $fecha, ?int $idTipoCita = null, ?int $ignorarIdCita = null, ?string $nit = null): Collection
    {
        $duracion = self::minutosDelTipo($idTipoCita);

        $hoy = now()->startOfDay();
        if ($fecha->lt($hoy) || $fecha->gt($hoy->copy()->addDays(self::DIAS_MAXIMOS))) {
            return collect();
        }

        // Los festivos de Colombia no se atienden (API de festivos, app/Support/ApisExternas.php):
        // ese día no hay turnos, así no se puede agendar ni desde la web ni desde recepción.
        if (self::esFestivo($fecha)) {
            return collect();
        }

        $dia = self::nombreDia($fecha);

        $bloques = self::deLaOptica(DB::table('horario')
            ->join('optometra', 'optometra.doc_optometra', '=', 'horario.id_optometra'), $nit)
            ->where('horario.dia_semana', $dia)
            // La terapia puede usar los bloques de terapia y los de cita; las demás citas, solo los de cita
            ->whereIn('horario.tipo_bloque', $duracion >= self::MINUTOS_TERAPIA ? ['terapia', 'cita'] : ['cita'])
            ->where('horario.estado', 'libre')
            ->whereIn('optometra.id_estado', [1, 3]) // activos (3 = cuenta nueva que aún no cambia la contraseña temporal)
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

        // Citas ya tomadas ese día (las canceladas no cuentan). Cada una ocupa
        // desde su hora hasta el final de su duración (la terapia dura 1 hora).
        $cancelado = self::estadoCancelado();
        $ocupadas = DB::table('asignacion_cita')
            ->leftJoin('tipo_cita', 'tipo_cita.id_tipo_cita', '=', 'asignacion_cita.id_tipo_cita')
            ->whereDate('asignacion_cita.fecha_cita', $fecha->toDateString())
            ->when($cancelado, fn ($q) => $q->where('asignacion_cita.id_estado', '!=', $cancelado))
            ->when($ignorarIdCita, fn ($q) => $q->where('asignacion_cita.id_cita', '!=', $ignorarIdCita))
            ->get(['asignacion_cita.id_optometra', 'asignacion_cita.hora_cita', 'tipo_cita.nombre_tipo'])
            ->map(function ($c) {
                $ini = self::aMinutos($c->hora_cita);

                return ['opt' => (string) $c->id_optometra, 'ini' => $ini, 'fin' => $ini + self::minutosPorNombre($c->nombre_tipo)];
            });

        $limiteHoy = $fecha->isToday() ? now()->addMinutes(30)->format('H:i') : null;

        $turnos = collect();
        foreach ($bloques as $b) {
            $inicio = Carbon::parse($fecha->toDateString().' '.$b->hora_inicio);
            $fin = Carbon::parse($fecha->toDateString().' '.$b->hora_fin);

            // Los turnos avanzan de a la duración de la cita (30 min o 1 hora)
            for ($t = $inicio->copy(); $t->copy()->addMinutes($duracion)->lte($fin); $t->addMinutes($duracion)) {
                $hora = $t->format('H:i');
                $ini = self::aMinutos($hora);
                $fn = $ini + $duracion;

                if ($limiteHoy && $hora < $limiteHoy) {
                    continue;
                }
                $choca = $ocupadas->contains(fn ($o) => $o['opt'] === (string) $b->id_optometra
                    && self::seCruzan($ini, $fn, $o['ini'], $o['fin']));
                if ($choca) {
                    continue;
                }
                $bloqueado = $bloqueos->contains(fn ($x) => (string) $x->id_optometra === (string) $b->id_optometra
                    && self::seCruzan($ini, $fn, self::aMinutos($x->hora_inicio), self::aMinutos($x->hora_fin)));
                if ($bloqueado) {
                    continue;
                }

                $turnos->push([
                    'hora' => $hora,
                    'id_optometra' => (string) $b->id_optometra,
                    'optometra' => $b->optometra,
                    'minutos' => $duracion,
                ]);
            }
        }

        return $turnos->unique(fn ($t) => $t['id_optometra'].'|'.$t['hora'])
            ->sortBy('hora')
            ->values();
    }

    /**
     * ¿Ese turno ya está ocupado por una cita (no cancelada)?
     * Se usa también al crear/editar citas desde el panel.
     */
    public static function citaOcupada(string $fecha, string $hora, string $idOptometra, ?int $ignorarIdCita = null, ?int $idTipoCita = null): bool
    {
        $cancelado = self::estadoCancelado();
        $ini = self::aMinutos($hora);
        $fin = $ini + self::minutosDelTipo($idTipoCita);

        return DB::table('asignacion_cita')
            ->leftJoin('tipo_cita', 'tipo_cita.id_tipo_cita', '=', 'asignacion_cita.id_tipo_cita')
            ->whereDate('asignacion_cita.fecha_cita', $fecha)
            ->where('asignacion_cita.id_optometra', $idOptometra)
            ->when($cancelado, fn ($q) => $q->where('asignacion_cita.id_estado', '!=', $cancelado))
            ->when($ignorarIdCita, fn ($q) => $q->where('asignacion_cita.id_cita', '!=', $ignorarIdCita))
            ->get(['asignacion_cita.hora_cita', 'tipo_cita.nombre_tipo'])
            ->contains(function ($c) use ($ini, $fin) {
                $i = self::aMinutos($c->hora_cita);

                return self::seCruzan($ini, $fin, $i, $i + self::minutosPorNombre($c->nombre_tipo));
            });
    }

    /**
     * ¿La fecha es festivo? Si la API de festivos no responde se devuelve false (no se bloquea la agenda)
     * y el calendario muestra el aviso de que no se pudieron consultar los festivos.
     */
    public static function esFestivo(Carbon $fecha): bool
    {
        return (bool) (ApisExternas::esFestivo($fecha->toDateString())['festivo'] ?? false);
    }

    public static function turnoDisponible(Carbon $fecha, string $hora, string $idOptometra, ?int $idTipoCita = null, ?string $nit = null): bool
    {
        return self::turnosLibres($fecha, $idTipoCita, null, $nit)
            ->contains(fn ($t) => $t['hora'] === $hora && $t['id_optometra'] === $idOptometra);
    }

    /**
     * Reglas motivo → tipo de cita: [texto que contiene el nombre del motivo, texto que contiene el nombre del tipo].
     * Se revisan en orden y gana la primera que coincida (por eso "adaptación" va antes que "control").
     */
    protected const TIPO_POR_MOTIVO = [
        ['adaptaci', 'adaptaci'],
        ['dolor', 'urgencia'],
        ['control', 'control'],
        ['pedi', 'examen visual'],
        ['fórmula', 'examen visual'],
        ['formula', 'examen visual'],
        ['convergencia', 'examen visual'],
        ['revisi', 'consulta'],
        ['rutina', 'consulta'],
    ];

    /** Id del tipo de cita que corresponde a un motivo (el tipo ya no se escoge aparte). */
    public static function tipoParaMotivo(?int $idMotivo): ?int
    {
        if (! $idMotivo) {
            return null;
        }

        $motivo = mb_strtolower((string) DB::table('motivos_cita')->where('id_motivo', $idMotivo)->value('nombre_motivo'));
        if ($motivo === '') {
            return null;
        }

        $tipos = DB::table('tipo_cita')->orderBy('id_tipo_cita')->get(['id_tipo_cita', 'nombre_tipo']);

        foreach (self::TIPO_POR_MOTIVO as [$enMotivo, $enTipo]) {
            if (mb_strpos($motivo, $enMotivo) === false) {
                continue;
            }
            $tipo = $tipos->first(fn ($x) => mb_strpos(mb_strtolower($x->nombre_tipo), $enTipo) !== false);
            if ($tipo) {
                return (int) $tipo->id_tipo_cita;
            }
        }

        // Sin regla que aplique: consulta general, o el primer tipo que exista.
        $general = $tipos->first(fn ($x) => mb_strpos(mb_strtolower($x->nombre_tipo), 'consulta') !== false);

        return (int) ($general->id_tipo_cita ?? $tipos->first()->id_tipo_cita ?? 0) ?: null;
    }

    /** [id_motivo => id_tipo_cita] para que el formulario escoja el tipo apenas se elige el motivo. */
    public static function mapaTipoPorMotivo(): array
    {
        $mapa = [];
        foreach (DB::table('motivos_cita')->pluck('id_motivo') as $id) {
            $mapa[(int) $id] = self::tipoParaMotivo((int) $id);
        }

        return $mapa;
    }

    /** Días de la semana en los que hay atención de citas (para orientar al paciente). */
    public static function diasConAtencion(?string $nit = null): array
    {
        $dias = self::deLaOptica(DB::table('horario')
            ->join('optometra', 'optometra.doc_optometra', '=', 'horario.id_optometra'), $nit)
            ->where('horario.tipo_bloque', 'cita')
            ->where('horario.estado', 'libre')
            ->whereIn('optometra.id_estado', [1, 3])
            ->distinct()
            ->pluck('horario.dia_semana')
            ->all();

        return array_values(array_filter(self::DIAS, fn ($d) => in_array($d, $dias, true)));
    }
}
