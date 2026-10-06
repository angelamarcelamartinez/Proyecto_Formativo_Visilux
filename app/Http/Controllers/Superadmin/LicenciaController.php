<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Licencia;
use App\Support\Licencias;
use App\Support\RegistroOptica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenciaController extends Controller
{
    /**
     * Solicitudes pendientes arriba e historial de todas las licencias abajo.
     */
    public function index(Request $request): View
    {
        Licencias::marcarVencidas();

        $estado = $request->get('estado', 'todas');

        $pendientes = Licencia::with(['empresa', 'plan'])
            ->where('estado', 'pendiente')
            ->orderBy('fecha_solicitud')
            ->get();

        $historial = Licencia::with(['empresa', 'plan'])
            ->where('estado', '<>', 'pendiente')
            ->when($estado !== 'todas', fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_solicitud')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.licencias.index', compact('pendientes', 'historial', 'estado'));
    }

    public function aprobar(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'referencia_pago' => ['nullable', 'string', 'max:120'],
            'valor' => ['nullable', 'numeric', 'min:0'],
        ]);

        $licencia = Licencia::with(['empresa', 'plan'])->findOrFail($id);
        $referencia = $data['referencia_pago'] ?? null;
        $valor = isset($data['valor']) ? (float) $data['valor'] : null;

        // Óptica nueva registrada desde /planes: se activa y se le envían las credenciales.
        if ($licencia->empresa->estaPendiente()) {
            $acceso = RegistroOptica::aprobar($licencia, $referencia, $valor);

            if ($acceso['enviado']) {
                return back()->with('success', "Registro aprobado: {$licencia->empresa->nombre} ya está activa. "
                    . "Enviamos la contraseña a {$acceso['correo']}.");
            }

            return back()->with('success', "Registro aprobado: {$licencia->empresa->nombre} ya está activa, pero no se pudo enviar el correo. "
                . "Entrégale estos datos al administrador: correo {$acceso['correo']}, contraseña {$acceso['password']}");
        }

        $licencia = Licencias::aprobar($licencia, $referencia, $valor);
        $avisada = Licencias::notificarActivacion($licencia);

        return back()->with('success', "Pago aprobado: {$licencia->empresa->nombre} tiene {$licencia->plan->nombre} hasta el "
            . $licencia->fecha_fin->format('d/m/Y') . '.'
            . ($avisada ? ' Le enviamos la confirmación por correo.' : ' No se pudo enviar el correo de confirmación.'));
    }

    public function rechazar(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['motivo' => ['nullable', 'string', 'max:500']]);

        $licencia = Licencia::with(['empresa', 'plan'])->findOrFail($id);

        if ($licencia->empresa->estaPendiente()) {
            RegistroOptica::rechazar($licencia);

            return back()->with('success', "Registro de {$licencia->empresa->nombre} rechazado y eliminado.");
        }

        Licencias::rechazar($licencia, $data['motivo'] ?? null);

        return back()->with('success', "Solicitud de {$licencia->empresa->nombre} rechazada.");
    }
}
