<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $planes = Plan::withCount([
            'licencias as licencias_activas' => fn ($q) => $q->where('estado', 'activa'),
        ])->orderBy('meses')->get();

        return view('superadmin.planes.index', compact('planes'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'meses' => ['required', 'integer', 'min:1', 'max:60'],
            'precio' => ['required', 'numeric', 'min:0'],
        ]);

        $plan->update([
            'nombre' => $data['nombre'],
            'meses' => $data['meses'],
            'precio' => $plan->es_prueba ? 0 : $data['precio'],
            'destacado' => $request->boolean('destacado'),
            'activo' => $request->boolean('activo'),
        ]);

        Actividad::registrar(null, 'editar', "Plan {$plan->nombre} actualizado", 'plan', $plan->id_plan);

        return back()->with('success', "Plan {$plan->nombre} actualizado.");
    }
}
