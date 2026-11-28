<?php

namespace App\Http\Controllers\Optometra;

use App\Http\Controllers\Controller;
use App\Support\Alcance;
use App\Support\Optometria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pacientes: los que ha atendido el optómetra y, con el buscador, cualquier paciente de la óptica.
 * La ficha reúne citas, historias, evolución de la fórmula, terapias y simulaciones.
 */
class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        // Mis pacientes: quienes tienen o tuvieron cita conmigo.
        $mios = DB::table('asignacion_cita as c')
            ->join('usuario as pac', 'pac.documento', '=', 'c.id_usuario')
            ->leftJoin('historia_clinica as hc', 'hc.id_asignacioncita', '=', 'c.id_cita')
            ->where('c.id_optometra', Optometria::doc())
            ->whereIn('c.id_cita', Optometria::idsCitasOptica())
            ->groupBy('pac.documento', 'pac.nombres', 'pac.apellido', 'pac.telefono', 'pac.email')
            ->selectRaw('pac.documento, pac.nombres, pac.apellido, pac.telefono, pac.email,
                         COUNT(DISTINCT c.id_cita) as citas, COUNT(DISTINCT hc.id_historia_clinica) as historias,
                         MAX(c.fecha_cita) as ultima_cita')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($s) use ($q) {
                    $s->where('pac.documento', 'like', "{$q}%")
                        ->orWhere('pac.nombres', 'like', "%{$q}%")
                        ->orWhere('pac.apellido', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('ultima_cita')
            ->paginate(15)
            ->withQueryString();

        // Buscador: otros pacientes (clientes) de la óptica que aún no ha atendido.
        $otros = collect();
        if ($q !== '') {
            $otros = Alcance::restringir(DB::table('usuario'), 'usuario')
                ->where('usuario.id_rol', 2)
                ->whereNotIn('usuario.documento', DB::table('asignacion_cita')->where('id_optometra', Optometria::doc())->select('id_usuario'))
                ->where(function ($s) use ($q) {
                    $s->where('usuario.documento', 'like', "{$q}%")
                        ->orWhere('usuario.nombres', 'like', "%{$q}%")
                        ->orWhere('usuario.apellido', 'like', "%{$q}%");
                })
                ->orderBy('usuario.nombres')
                ->limit(10)
                ->get(['usuario.documento', 'usuario.nombres', 'usuario.apellido', 'usuario.telefono', 'usuario.email']);
        }

        return view('optometra.pacientes.index', compact('mios', 'otros', 'q'));
    }

    public function show(string $documento): View
    {
        $paciente = Optometria::paciente($documento);
        abort_unless($paciente, 404, 'Ese paciente no pertenece a tu óptica.');

        $citas = Optometria::citas()
            ->leftJoin('optometra as o', 'o.doc_optometra', '=', 'asignacion_cita.id_optometra')
            ->addSelect(DB::raw("CONCAT(o.nombre, ' ', o.apellido) as optometra"))
            ->where('asignacion_cita.id_usuario', $documento)
            ->orderByDesc('asignacion_cita.fecha_cita')->orderByDesc('asignacion_cita.hora_cita')
            ->get();

        $historias = DB::table('historia_clinica as hc')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->leftJoin('optometra as o', 'o.doc_optometra', '=', 'c.id_optometra')
            ->leftJoin('diagnostico as d', 'd.id_diagnostico', '=', 'hc.id_diagnostico')
            ->leftJoin('enfermedades as e', 'e.id_enfermedad', '=', 'd.id_enfermedad')
            ->where('c.id_usuario', $documento)
            ->whereIn('hc.id_asignacioncita', Optometria::idsCitasOptica())
            ->orderByDesc('hc.fecha_registro')
            ->get(['hc.id_historia_clinica', 'hc.fecha_registro', 'hc.tratamiento', 'hc.id_formula', 'c.id_optometra',
                DB::raw("CONCAT(o.nombre, ' ', o.apellido) as optometra"), 'e.nombre_enfer', 'e.codigo']);

        // Evolución de la fórmula (de la más antigua a la más reciente, para la gráfica)
        $formulas = DB::table('formula_medica')
            ->where('documento_paciente', $documento)
            ->orderBy('fecha_formula')->orderBy('id_formula')
            ->get();

        $terapias = DB::table('agendamiento_procedimiento as ap')
            ->join('historia_clinica as hc', 'hc.id_historia_clinica', '=', 'ap.id_historia_clinica')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->leftJoin('tipo_terapia as tt', 'tt.id_tipo_terapia', '=', 'ap.id_tipo_terapia')
            ->where('c.id_usuario', $documento)
            ->whereIn('c.id_cita', Optometria::idsCitasOptica())
            ->orderByDesc('ap.fecha_inicio')
            ->get(['ap.*', 'tt.nomtipo_terp',
                DB::raw('(SELECT COUNT(*) FROM evolucion_terapia ev WHERE ev.id_agendamiento_procedimiento = ap.id_agendamiento_procedimiento) as sesiones')]);

        $simulaciones = Optometria::tieneSimulaciones()
            ? DB::table('simulacion_visual')->where('documento_paciente', $documento)->orderByDesc('fecha')->limit(20)->get()
            : collect();

        // Condiciones del simulador relacionadas con sus diagnósticos
        $condiciones = $historias
            ->map(fn ($h) => Optometria::condicionSimulador($h->codigo, $h->nombre_enfer))
            ->filter()->unique()->values();

        // Cita mía pendiente para atender ahora (botón directo)
        $porAtender = $citas->first(fn ($c) => (string) $c->id_optometra === Optometria::doc()
            && ! $c->id_historia_clinica
            && ! in_array((int) $c->id_estado, array_filter([Optometria::estado('Cancelado'), Optometria::estado('Completado')]), true));

        return view('optometra.pacientes.show', compact(
            'paciente', 'citas', 'historias', 'formulas', 'terapias', 'simulaciones', 'condiciones', 'porAtender'
        ));
    }
}
