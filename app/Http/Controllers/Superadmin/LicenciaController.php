<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Licencia;
use App\Support\Licencias;
use App\Support\RegistroOptica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Licencias: historial (con descarga en Excel y PDF) y ópticas que requieren atención.
 * Aprobar o rechazar las solicitudes de plan se hace desde el Panel general.
 */
class LicenciaController extends Controller
{
    public function index(Request $request): View
    {
        Licencias::marcarVencidas();

        // Ópticas que requieren atención: por vencer, vencidas o sin plan
        // (se filtra en PHP: estado_licencia es una columna calculada de la vista y compararla en SQL
        // puede chocar con la collation de la conexión)
        $alertas = DB::table('v_estado_licencias')
            ->get()
            ->whereIn('estado_licencia', ['por_vencer', 'vencida', 'sin_licencia'])
            ->sortBy(fn ($o) => $o->dias_restantes ?? -9999)
            ->values();

        // Historial de licencias de todas las ópticas (los pagos por aprobar están en el Panel general)
        $estadoLic = in_array($request->get('lic'), ['activa', 'vencida', 'cancelada'], true) ? $request->get('lic') : 'todas';

        $historial = Licencia::with(['empresa', 'plan'])
            ->where('estado', '<>', 'pendiente')
            ->when($estadoLic !== 'todas', fn ($q) => $q->where('estado', $estadoLic))
            ->orderByDesc('fecha_solicitud')
            ->paginate(10, ['*'], 'hist')
            ->withQueryString()
            ->fragment('historial');

        return view('superadmin.licencias.index', compact('alertas', 'historial', 'estadoLic'));
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

        // Óptica nueva registrada desde /planes: se activa y se envían las credenciales al correo de la óptica.
        if ($licencia->empresa->estaPendiente()) {
            // Antes de activar una óptica nueva hay que verificar su NIT con la Cámara de Comercio:
            // el superadmin revisa el PDF y lo confirma en la ventana emergente (casilla "confirmo").
            if (! $licencia->empresa->tieneCamara()) {
                return back()->withErrors(['general' => "{$licencia->empresa->nombre} no tiene certificado de la Cámara de Comercio cargado: edita la óptica y adjunta el PDF antes de aprobar."]);
            }
            if (! $request->boolean('confirmo')) {
                return back()->withErrors(['general' => "Antes de aprobar, revisa el certificado y confirma que verificaste el NIT de {$licencia->empresa->nombre} con la Cámara de Comercio (botón «Verificar NIT y aprobar»)."]);
            }

            $acceso = RegistroOptica::aprobar($licencia, $referencia, $valor);

            if ($acceso['enviado']) {
                return back()->with('success', "Registro aprobado: {$licencia->empresa->nombre} ya está activa. "
                    . "Enviamos la contraseña temporal al correo de la óptica ({$acceso['correo']}); el administrador entra con {$acceso['usuario']}.");
            }

            return back()->with('success', "Registro aprobado: {$licencia->empresa->nombre} ya está activa, pero no se pudo enviar el correo. "
                . "Entrégale estos datos al administrador: usuario {$acceso['usuario']}, contraseña temporal {$acceso['password']}. "
                . "No se pudo enviar a: " . \Illuminate\Support\Str::limit((string) $acceso['error'], 200));
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
