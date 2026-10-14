<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Licencia;
use App\Support\Licencias;
use App\Support\RegistroOptica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aprobar o rechazar las solicitudes de plan. Los botones están en el Panel general.
 */
class LicenciaController extends Controller
{
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
                . "Entrégale estos datos al administrador: usuario {$acceso['usuario']}, contraseña temporal {$acceso['password']}");
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
