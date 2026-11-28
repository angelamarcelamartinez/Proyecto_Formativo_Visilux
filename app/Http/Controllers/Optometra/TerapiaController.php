<?php

namespace App\Http\Controllers\Optometra;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Support\Optometria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Terapias (agendamiento_procedimiento) que el optómetra dejó programadas desde una consulta,
 * con su evolución sesión por sesión (evolucion_terapia).
 */
class TerapiaController extends Controller
{
    /** Terapias de la óptica con datos del paciente; $soloMias limita a las del optómetra logueado. */
    protected function base(bool $soloMias = true)
    {
        return DB::table('agendamiento_procedimiento as ap')
            ->join('historia_clinica as hc', 'hc.id_historia_clinica', '=', 'ap.id_historia_clinica')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->join('usuario as pac', 'pac.documento', '=', 'c.id_usuario')
            ->leftJoin('tipo_terapia as tt', 'tt.id_tipo_terapia', '=', 'ap.id_tipo_terapia')
            ->whereIn('c.id_cita', Optometria::idsCitasOptica())
            ->when($soloMias, fn ($q) => $q->where('c.id_optometra', Optometria::doc()))
            ->select([
                'ap.*', 'tt.nomtipo_terp', 'tt.descripcion as tipo_descripcion',
                'pac.documento', 'pac.nombres', 'pac.apellido', 'pac.telefono', 'c.id_optometra',
                DB::raw('(SELECT COUNT(*) FROM evolucion_terapia ev WHERE ev.id_agendamiento_procedimiento = ap.id_agendamiento_procedimiento) as sesiones'),
                DB::raw('(SELECT MAX(ev.fecha) FROM evolucion_terapia ev WHERE ev.id_agendamiento_procedimiento = ap.id_agendamiento_procedimiento) as ultima_sesion'),
                DB::raw('(SELECT COALESCE(SUM(dp.cantidad), 0) FROM detalle_formula_medica_procedimiento dp WHERE dp.id_formula = ap.id_formula) as sesiones_plan'),
            ]);
    }

    public function index(Request $request): View
    {
        $estado = $request->get('estado', 'Activo');
        if (! in_array($estado, array_merge(Optometria::ESTADOS_TERAPIA, ['todas']), true)) {
            $estado = 'Activo';
        }

        $terapias = $this->base()
            ->when($estado !== 'todas', fn ($q) => $q->where('ap.estado', $estado))
            ->orderByDesc('ap.fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        $conteos = DB::table('agendamiento_procedimiento as ap')
            ->join('historia_clinica as hc', 'hc.id_historia_clinica', '=', 'ap.id_historia_clinica')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->where('c.id_optometra', Optometria::doc())
            ->groupBy('ap.estado')
            ->selectRaw('ap.estado, COUNT(*) as total')
            ->pluck('total', 'estado');

        return view('optometra.terapias.index', compact('terapias', 'estado', 'conteos'));
    }

    public function show(int $id): View
    {
        $t = $this->base(false)->where('ap.id_agendamiento_procedimiento', $id)->first();
        abort_unless($t, 404, 'Terapia no encontrada.');

        return view('optometra.terapias.show', [
            't' => $t,
            'esMia' => (string) $t->id_optometra === Optometria::doc(),
            'evoluciones' => DB::table('evolucion_terapia')
                ->where('id_agendamiento_procedimiento', $id)
                ->orderByDesc('fecha')
                ->get(),
        ]);
    }

    public function evolucion(Request $request, int $id): RedirectResponse
    {
        $t = $this->base()->where('ap.id_agendamiento_procedimiento', $id)->first();
        abort_unless($t, 403, 'Solo puedes registrar la evolución de tus terapias.');

        if ($t->estado !== 'Activo') {
            return back()->withErrors(['general' => "La terapia está {$t->estado}: reactívala para registrar sesiones."]);
        }

        $data = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:now'],
            'descripcion_evolucion' => ['required', 'string', 'max:2000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [], ['descripcion_evolucion' => 'evolución']);

        DB::table('evolucion_terapia')->insert([
            'id_agendamiento_procedimiento' => $id,
            'fecha' => $data['fecha'],
            'descripcion_evolucion' => $data['descripcion_evolucion'],
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        Actividad::registrar(Auth::user()->nit_empresa, 'crear', "Optómetra registró sesión de terapia de {$t->nombres}", 'evolucion_terapia', $id);

        return back()->with('success', 'Sesión registrada.');
    }

    public function estado(Request $request, int $id): RedirectResponse
    {
        $t = $this->base()->where('ap.id_agendamiento_procedimiento', $id)->first();
        abort_unless($t, 403, 'Solo puedes cambiar el estado de tus terapias.');

        $data = $request->validate(['estado' => ['required', Rule::in(Optometria::ESTADOS_TERAPIA)]]);

        DB::table('agendamiento_procedimiento')->where('id_agendamiento_procedimiento', $id)->update([
            'estado' => $data['estado'],
            // Al finalizar, si no tenía fecha final se cierra hoy.
            'fecha_final' => $data['estado'] === 'Finalizado' && ! $t->fecha_final ? now() : $t->fecha_final,
        ]);

        Actividad::registrar(Auth::user()->nit_empresa, 'editar', "Optómetra: terapia #{$id} → {$data['estado']}", 'agendamiento_procedimiento', $id);

        return back()->with('success', "Terapia marcada como {$data['estado']}.");
    }
}
