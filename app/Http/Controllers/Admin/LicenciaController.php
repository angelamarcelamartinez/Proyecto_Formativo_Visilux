<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Plan;
use App\Support\Licencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * "Mi licencia": el admin de la óptica ve su plan, sus días y pide renovar.
 * Esta pantalla sigue abierta aunque la licencia esté vencida.
 */
class LicenciaController extends Controller
{
    public function index(): View
    {
        Licencias::marcarVencidas();

        $empresa = Auth::user()->empresa;

        return view('admin.licencia.index', [
            'empresa' => $empresa,
            'vigente' => $empresa?->licenciaVigente(),
            'diasRestantes' => $empresa?->diasRestantes(),
            'coberturaHasta' => $empresa?->coberturaHasta(),
            'pendiente' => $empresa?->licencias()->with('plan')->where('estado', 'pendiente')->first(),
            'historial' => $empresa ? $empresa->licencias()->with('plan')->orderByDesc('fecha_solicitud')->get() : collect(),
            'planes' => Plan::where('activo', 1)->orderBy('meses')->get(),
            'puedeIngresar' => Licencias::puedeIngresar($empresa),
        ]);
    }

    public function solicitar(Request $request): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        abort_unless($empresa && ! $empresa->estaSuspendida(), 403);

        $data = $request->validate(['id_plan' => ['required', 'exists:plan,id_plan']]);

        $licencia = Licencias::solicitar($empresa, Plan::findOrFail($data['id_plan']));

        return back()->with('success', "Solicitaste el plan {$licencia->plan->nombre}. Lo activaremos cuando confirmemos el pago.");
    }

    public function cancelar(int $id): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        abort_unless($empresa, 403);

        $licencia = $empresa->licencias()->where('estado', 'pendiente')->findOrFail($id);
        $licencia->update(['estado' => 'cancelada', 'observaciones' => 'Cancelada por la óptica']);

        Actividad::registrar($empresa->nit, 'licencia', 'Canceló su solicitud de plan', 'licencia', $licencia->id_licencia);

        return back()->with('success', 'Cancelaste la solicitud.');
    }
}
