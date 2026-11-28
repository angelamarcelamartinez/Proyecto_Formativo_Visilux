<?php

namespace App\Http\Controllers\Optometra;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Usuario;
use App\Support\Optometria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Inicio del optómetra: su día, su agenda, su horario y su perfil profesional.
 */
class PanelController extends Controller
{
    /** "Mi día": citas de hoy, próximas y resumen. */
    public function index(): View
    {
        $hoy = now()->toDateString();
        $cancelado = Optometria::estado('Cancelado');
        $completado = Optometria::estado('Completado');

        $citasHoy = Optometria::misCitas()
            ->whereDate('asignacion_cita.fecha_cita', $hoy)
            ->orderBy('asignacion_cita.hora_cita')
            ->get();

        $proximas = Optometria::misCitas()
            ->whereDate('asignacion_cita.fecha_cita', '>', $hoy)
            ->whereDate('asignacion_cita.fecha_cita', '<=', now()->addDays(7)->toDateString())
            ->when($cancelado, fn ($q) => $q->where('asignacion_cita.id_estado', '!=', $cancelado))
            ->orderBy('asignacion_cita.fecha_cita')->orderBy('asignacion_cita.hora_cita')
            ->limit(12)
            ->get();

        // Citas pasadas que quedaron sin historia (ni canceladas): hay que cerrarlas.
        $sinCerrar = Optometria::misCitas()
            ->whereDate('asignacion_cita.fecha_cita', '<', $hoy)
            ->whereNull('hc.id_historia_clinica')
            ->when($cancelado, fn ($q) => $q->where('asignacion_cita.id_estado', '!=', $cancelado))
            ->when($completado, fn ($q) => $q->where('asignacion_cita.id_estado', '!=', $completado))
            ->orderByDesc('asignacion_cita.fecha_cita')
            ->limit(10)
            ->get();

        $atendidasMes = Optometria::misCitas()
            ->whereNotNull('hc.id_historia_clinica')
            ->whereYear('hc.fecha_registro', now()->year)
            ->whereMonth('hc.fecha_registro', now()->month)
            ->count();

        $terapiasActivas = $this->misTerapias()->where('ap.estado', 'Activo')->count();

        // Controles (próximo control de la fórmula) de mis pacientes en los próximos 30 días.
        $controles = DB::table('formula_medica as f')
            ->join('historia_clinica as hc', 'hc.id_formula', '=', 'f.id_formula')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->join('usuario as pac', 'pac.documento', '=', 'f.documento_paciente')
            ->where('c.id_optometra', Optometria::doc())
            ->whereBetween('f.proximo_control', [$hoy, now()->addDays(30)->toDateString()])
            ->orderBy('f.proximo_control')
            ->limit(8)
            ->get(['f.proximo_control', 'pac.documento', 'pac.nombres', 'pac.apellido', 'pac.telefono']);

        return view('optometra.dashboard', [
            'citasHoy' => $citasHoy,
            'proximas' => $proximas,
            'sinCerrar' => $sinCerrar,
            'controles' => $controles,
            'stats' => [
                'hoy' => $citasHoy->count(),
                'por_atender' => $citasHoy->whereNull('id_historia_clinica')
                    ->filter(fn ($c) => (int) $c->id_estado !== (int) $cancelado)->count(),
                'mes' => $atendidasMes,
                'terapias' => $terapiasActivas,
            ],
        ]);
    }

    /** Agenda por semana (lunes a domingo) con filtro de estado. */
    public function agenda(Request $request): View
    {
        try {
            $fecha = Carbon::parse($request->get('fecha', now()->toDateString()));
        } catch (\Throwable $e) {
            $fecha = now();
        }

        $inicio = $fecha->copy()->startOfWeek();
        $fin = $fecha->copy()->endOfWeek();
        $estado = $request->integer('estado') ?: null;

        $citas = Optometria::misCitas()
            ->whereBetween(DB::raw('DATE(asignacion_cita.fecha_cita)'), [$inicio->toDateString(), $fin->toDateString()])
            ->when($estado, fn ($q) => $q->where('asignacion_cita.id_estado', $estado))
            ->orderBy('asignacion_cita.fecha_cita')->orderBy('asignacion_cita.hora_cita')
            ->get()
            ->groupBy(fn ($c) => Carbon::parse($c->fecha_cita)->toDateString());

        return view('optometra.agenda', [
            'fecha' => $fecha,
            'inicio' => $inicio,
            'fin' => $fin,
            'citas' => $citas,
            'estado' => $estado,
            'estados' => DB::table('estado')->whereIn('nom_estado', ['Pendiente', 'Confirmada', 'En proceso', 'Completado', 'Cancelado'])->get(),
        ]);
    }

    /**
     * Cambiar el estado de una cita propia:
     *  - "iniciar"  → En proceso (y abre la consulta)
     *  - "cancelar" → Cancelado (no asistió o se cancela; libera el turno)
     *  - "reabrir"  → Pendiente
     * "Completado" no se pone a mano: lo pone el trigger al guardar la historia clínica.
     */
    public function estadoCita(Request $request, int $id): RedirectResponse
    {
        $cita = Optometria::miCita($id);
        $accion = $request->input('accion');

        if ($cita->id_historia_clinica) {
            return back()->withErrors(['general' => 'Esa cita ya tiene historia clínica: no se puede cambiar su estado.']);
        }

        $destino = match ($accion) {
            'iniciar' => Optometria::estado('En proceso'),
            'cancelar' => Optometria::estado('Cancelado'),
            'reabrir' => Optometria::estado('Pendiente'),
            default => null,
        };
        abort_unless($destino, 422);

        $data = ['id_estado' => $destino];
        if ($accion === 'cancelar' && $request->filled('motivo')) {
            $data['observaciones'] = trim(($cita->observaciones ? $cita->observaciones . "\n" : '')
                . 'Cancelada por el optómetra: ' . mb_substr((string) $request->input('motivo'), 0, 200));
        }

        try {
            DB::table('asignacion_cita')->where('id_cita', $id)->update($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // Reabrir una cita cancelada: el trigger la rechaza si ese turno ya lo tomó otra cita.
            return back()->withErrors(['general' => self::mensajeBd($e)]);
        }

        Actividad::registrar(Auth::user()->nit_empresa, 'editar',
            "Optómetra: cita #{$id} → " . ['iniciar' => 'En proceso', 'cancelar' => 'Cancelada', 'reabrir' => 'Pendiente'][$accion],
            'asignacion_cita', $id);

        if ($accion === 'iniciar') {
            return redirect()->route('optometra.consulta.create', $id);
        }

        return back()->with('success', $accion === 'cancelar' ? 'Cita cancelada: el turno quedó libre.' : 'La cita volvió a quedar pendiente.');
    }

    /** Horario semanal del optómetra (lo administra el administrador en Admin › Horarios). */
    public function horario(): View
    {
        $orden = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $bloques = DB::table('horario')
            ->where('id_optometra', Optometria::doc())
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy('dia_semana');

        return view('optometra.horario', [
            'dias' => collect($orden)->mapWithKeys(fn ($d) => [$d => $bloques->get($d, collect())]),
            'sinDia' => $bloques->get('', collect()),
        ]);
    }

    public function perfil(): View
    {
        return view('optometra.perfil', [
            'perfil' => Optometria::perfil(),
            'usuario' => Auth::user(),
            'especialidades' => DB::table('especialidad')->orderBy('nombre_especialidad')->get(),
        ]);
    }

    /**
     * Datos de contacto y profesionales. Todo vive en la tabla `optometra` (el optómetra no tiene
     * fila en `usuario`); el celular no puede ser el de otro optómetra ni el de un usuario.
     */
    public function actualizarPerfil(Request $request): RedirectResponse
    {
        /** @var \App\Models\Optometra $user */
        $user = Auth::user();

        $data = $request->validate([
            'telefono' => ['required', 'string', 'max:20', Rule::unique('optometra', 'telefono')->ignore($user->documento, 'doc_optometra'), Rule::unique('usuario', 'telefono')],
            'tarjeta_profesional' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
        ], [], ['telefono' => 'celular', 'tarjeta_profesional' => 'tarjeta profesional', 'id_especialidad' => 'especialidad']);

        try {
            DB::transaction(function () use ($user, $data) {
                DB::table('optometra')->where('doc_optometra', $user->documento)->update([
                    'telefono' => $data['telefono'],
                    'tarjeta_profesional' => $data['tarjeta_profesional'],
                    'id_especialidad' => $data['id_especialidad'],
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->withErrors(['general' => self::mensajeBd($e)]);
        }

        Actividad::registrar($user->nit_empresa, 'editar', 'Optómetra actualizó sus datos profesionales', 'optometra', $user->documento);

        return back()->with('success', 'Datos profesionales actualizados.');
    }

    /** Terapias (agendamientos) que nacieron de una historia del optómetra. */
    protected function misTerapias()
    {
        return DB::table('agendamiento_procedimiento as ap')
            ->join('historia_clinica as hc', 'hc.id_historia_clinica', '=', 'ap.id_historia_clinica')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->where('c.id_optometra', Optometria::doc());
    }

    /** Mensaje legible de un error de la BD (los SIGNAL de los triggers traen su propio texto). */
    public static function mensajeBd(\Illuminate\Database\QueryException $e): string
    {
        $info = $e->errorInfo;
        if (($info[0] ?? null) === '45000' && ! empty($info[2])) {
            return $info[2];
        }
        if ((int) ($info[1] ?? 0) === 1062) {
            return 'Ese dato ya está registrado por otra persona (celular, correo o documento repetido).';
        }
        report($e);

        return 'No se pudo guardar en la base de datos. Revisa los datos e inténtalo de nuevo.';
    }
}
