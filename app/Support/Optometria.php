<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas compartidas del módulo del optómetra.
 *
 *  - El optómetra NO está en `usuario`: toda su información y su cuenta (correo y contraseña)
 *    viven en la tabla `optometra`. Inicia sesión con App\Auth\DosTablasUserProvider y
 *    Auth::user() es un App\Models\Optometra (Auth::id() = doc_optometra).
 *  - Solo ATIENDE y modifica lo suyo (citas con id_optometra = su documento).
 *  - Puede CONSULTAR la historia de cualquier paciente de su óptica (continuidad clínica),
 *    usando el mismo filtro por óptica que el panel del administrador (Alcance).
 */
class Optometria
{
    public const ESTADOS_TERAPIA = ['Activo', 'Finalizado', 'Suspendido'];

    public const CORRECCIONES = ['Lentes aéreos', 'Lentes bifocales', 'Lentes progresivos', 'Lentes de contacto', 'Lentes ocupacionales', 'Sin corrección'];

    public const FORMAS_USO = ['Permanente', 'Visión lejana', 'Visión próxima', 'Uso ocasional', 'Uso nocturno'];

    public const OJOS = ['OD' => 'Ojo derecho', 'OI' => 'Ojo izquierdo', 'AO' => 'Ambos ojos'];

    public const TIPOS_PROCEDIMIENTO = ['terapia visual', 'examen visual', 'adaptacion lentes contacto'];

    /** Documento del optómetra con la sesión abierta. */
    public static function doc(): string
    {
        return (string) Auth::id();
    }

    /** Fila de `optometra` del usuario logueado (null si todavía no existe). */
    public static function perfil(): ?object
    {
        static $perfil = false;

        if ($perfil === false) {
            $perfil = DB::table('optometra')
                ->leftJoin('especialidad', 'especialidad.id_especialidad', '=', 'optometra.id_especialidad')
                ->where('optometra.doc_optometra', self::doc())
                ->first(['optometra.*', 'especialidad.nombre_especialidad']);
        }

        return $perfil;
    }

    /** id de un estado de la tabla `estado` por su nombre ("Pendiente", "Completado"...). */
    public static function estado(string $nombre): ?int
    {
        static $cache = [];

        if (! array_key_exists($nombre, $cache)) {
            $cache[$nombre] = DB::table('estado')->where('nom_estado', $nombre)->value('id_estado');
        }

        return $cache[$nombre] !== null ? (int) $cache[$nombre] : null;
    }

    /** Citas de la óptica con los datos que se muestran en agenda y panel. */
    public static function citas(): Builder
    {
        $q = DB::table('asignacion_cita')
            ->join('usuario as pac', 'pac.documento', '=', 'asignacion_cita.id_usuario')
            ->leftJoin('tipo_cita', 'tipo_cita.id_tipo_cita', '=', 'asignacion_cita.id_tipo_cita')
            ->leftJoin('motivos_cita', 'motivos_cita.id_motivo', '=', 'asignacion_cita.id_motivo')
            ->leftJoin('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado')
            ->leftJoin('historia_clinica as hc', 'hc.id_asignacioncita', '=', 'asignacion_cita.id_cita')
            ->select([
                'asignacion_cita.id_cita', 'asignacion_cita.fecha_cita', 'asignacion_cita.hora_cita',
                'asignacion_cita.observaciones', 'asignacion_cita.id_estado', 'asignacion_cita.id_optometra',
                'asignacion_cita.id_usuario',
                'pac.nombres', 'pac.apellido', 'pac.telefono', 'pac.email',
                'tipo_cita.nombre_tipo', 'motivos_cita.nombre_motivo', 'estado.nom_estado',
                'hc.id_historia_clinica',
            ]);

        // El NIT se guarda de varias formas ('9000000001', '900000000-1'); se comparan los 9 primeros dígitos,
        // igual que recepción al crear la cita. Así la cita agendada por recepción siempre aparece en la agenda.
        $base = substr(preg_replace('/\D/', '', (string) Alcance::nit()), 0, 9);
        if ($base === '') {
            return $q->whereRaw('1 = 0');
        }

        return $q->whereRaw('SUBSTR(REPLACE(REPLACE(REPLACE(CAST(asignacion_cita.nit_empresa AS CHAR), \'-\', \'\'), \'.\', \'\'), \' \', \'\'), 1, 9) = ?', [$base]);
    }

    /** Solo las citas del optómetra logueado. */
    public static function misCitas(): Builder
    {
        return self::citas()->where('asignacion_cita.id_optometra', self::doc());
    }

    /** Una cita del optómetra logueado o 404. */
    public static function miCita(int $idCita): object
    {
        $cita = self::misCitas()->where('asignacion_cita.id_cita', $idCita)->first();
        abort_unless($cita, 404, 'Esa cita no está en tu agenda.');

        return $cita;
    }

    /** Ids de las citas de la óptica (subconsulta para filtrar historias). */
    public static function idsCitasOptica(): Builder
    {
        return Alcance::restringir(DB::table('asignacion_cita')->select('asignacion_cita.id_cita'), 'asignacion_cita');
    }

    /**
     * Historia clínica visible para el optómetra (de su óptica) con cita, paciente y optómetra.
     * $soloMia = true exige que la cita sea suya (para editar).
     */
    public static function historia(int $id, bool $soloMia = false): object
    {
        $h = DB::table('historia_clinica as hc')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->join('usuario as pac', 'pac.documento', '=', 'c.id_usuario')
            ->leftJoin('optometra as o', 'o.doc_optometra', '=', 'c.id_optometra')
            ->leftJoin('especialidad as esp', 'esp.id_especialidad', '=', 'o.id_especialidad')
            ->leftJoin('tipo_cita as tc', 'tc.id_tipo_cita', '=', 'c.id_tipo_cita')
            ->leftJoin('motivos_cita as m', 'm.id_motivo', '=', 'c.id_motivo')
            ->leftJoin('tipo_documento as td', 'td.id_tipo_docu', '=', 'pac.id_tipo_docu')
            ->where('hc.id_historia_clinica', $id)
            ->whereIn('hc.id_asignacioncita', self::idsCitasOptica())
            ->when($soloMia, fn ($q) => $q->where('c.id_optometra', self::doc()))
            ->first([
                'hc.*', 'c.fecha_cita', 'c.hora_cita', 'c.id_optometra', 'c.id_usuario', 'c.observaciones as obs_cita',
                'pac.nombres', 'pac.apellido', 'pac.telefono', 'pac.email', 'pac.documento', 'td.nom_ti_docu',
                'o.nombre as opt_nombre', 'o.apellido as opt_apellido', 'o.tarjeta_profesional', 'esp.nombre_especialidad as opt_especialidad',
                'tc.nombre_tipo', 'm.nombre_motivo',
            ]);

        abort_unless($h, 404, $soloMia ? 'Solo puedes modificar las historias de tus propias citas.' : 'Historia clínica no encontrada.');

        return $h;
    }

    /** Paciente (usuario) que pertenece a la óptica o tiene citas en ella. */
    public static function paciente(string $documento): ?object
    {
        $nit = Alcance::nit();

        return DB::table('usuario')
            ->leftJoin('tipo_documento as td', 'td.id_tipo_docu', '=', 'usuario.id_tipo_docu')
            ->leftJoin('ciudad', 'ciudad.id_ciudad', '=', 'usuario.id_ciudad')
            ->where('usuario.documento', $documento)
            ->where(function ($q) use ($nit) {
                $q->where('usuario.nit_empresa', $nit)
                    ->orWhereIn('usuario.documento', self::idsCitasOptica()->select('asignacion_cita.id_usuario'));
            })
            ->first(['usuario.*', 'td.nom_ti_docu', 'ciudad.nombre_ciudad']);
    }

    /** Diagnósticos (con nombre y código) de una fórmula. */
    public static function diagnosticos(?int $idFormula): \Illuminate\Support\Collection
    {
        if (! $idFormula) {
            return collect();
        }

        return DB::table('diagnostico as d')
            ->leftJoin('enfermedades as e', 'e.id_enfermedad', '=', 'd.id_enfermedad')
            ->where('d.id_formula', $idFormula)
            ->orderBy('d.id_diagnostico')
            ->get(['d.*', 'e.nombre_enfer', 'e.codigo', 'e.descripcion']);
    }

    /** ¿Ya se ejecutó el script SQL del módulo? (tabla de simulaciones) */
    public static function tieneSimulaciones(): bool
    {
        static $tiene = null;

        return $tiene ??= Schema::hasTable('simulacion_visual');
    }

    /**
     * Condición del simulador que corresponde a una enfermedad (por código CIE-10 o por nombre).
     * Devuelve la clave ('glc', 'myopia'...) o null si el simulador no la muestra.
     */
    public static function condicionSimulador(?string $codigo, ?string $nombre = null): ?string
    {
        $codigo = strtoupper(trim((string) $codigo));
        $nombre = mb_strtolower(trim((string) $nombre));

        foreach (config('simulador.condiciones') as $clave => $c) {
            foreach ($c['cie'] as $prefijo) {
                if ($codigo !== '' && str_starts_with($codigo, strtoupper($prefijo))) {
                    return $clave;
                }
            }
            foreach ($c['claves'] as $palabra) {
                if ($nombre !== '' && str_contains($nombre, $palabra)) {
                    return $clave;
                }
            }
        }

        return null;
    }

    /** Clases de color para la etiqueta de un estado (cita o terapia). */
    public static function colorEstado(?string $estado): string
    {
        return match (mb_strtolower(trim((string) $estado))) {
            'completado', 'finalizado', 'entregado' => 'op-chip op-chip-ok',
            'pendiente' => 'op-chip op-chip-warn',
            'confirmada' => 'op-chip op-chip-ok',
            'en proceso', 'activo' => 'op-chip op-chip-info',
            'cancelado', 'suspendido', 'inactivo' => 'op-chip op-chip-bad',
            default => 'op-chip',
        };
    }

    /** "-1.25" / "+0.50" / "0.00" con signo, como se escribe una fórmula. */
    public static function dioptria($valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }
        $v = (float) $valor;

        return ($v > 0 ? '+' : '') . number_format($v, 2, '.', '');
    }
}
