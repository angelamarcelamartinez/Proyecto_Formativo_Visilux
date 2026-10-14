<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Licencias;
use App\Support\PagoSimulado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Renovación de la licencia desde el panel del administrador.
 *
 * Con la licencia vencida el administrador sigue pudiendo entrar, pero esta pantalla se le pone
 * encima del panel y no la puede cerrar: elige plan, paga con tarjeta (simulado) y queda a la
 * espera de que el superadmin acepte la renovación. Las credenciales no cambian.
 */
class RenovacionController extends Controller
{
    public function index(Request $request): View
    {
        Licencias::marcarVencidas();

        $empresa = Auth::user()->empresa;
        abort_unless(Licencias::adminPuedeEntrar($empresa), 403);

        $ultima = $empresa->licencias()->with('plan')->orderByDesc('fecha_solicitud')->orderByDesc('id_licencia')->first();

        return view('admin.renovacion.index', [
            'empresa' => $empresa,
            'bloqueada' => ! Licencias::puedeIngresar($empresa),
            'pendiente' => $empresa->licencias()->with('plan')->where('estado', 'pendiente')->first(),
            'rechazada' => $ultima && $ultima->estado === 'cancelada' && $ultima->observaciones !== 'Cancelada por la óptica' ? $ultima : null,
            'vencioEl' => $empresa->licencias()->whereIn('estado', ['vencida', 'inactiva', 'activa'])->max('fecha_fin'),
            'planes' => Plan::where('activo', 1)->where('es_prueba', 0)->orderBy('meses')->get(),
            'planElegido' => (int) $request->query('plan'),
            'pageTitle' => 'Renovar licencia ·',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        abort_unless(Licencias::adminPuedeEntrar($empresa), 403);

        $datos = $request->validate([
            'id_plan' => ['required', 'exists:plan,id_plan'],
            'tarjeta_titular' => ['required', 'string', 'max:100'],
            'tarjeta_numero' => ['required', 'string', 'max:23'],
            'tarjeta_vencimiento' => ['required', 'string', 'max:5'],
            'tarjeta_cvv' => ['required', 'string', 'max:4'],
        ], [], [
            'id_plan' => 'plan', 'tarjeta_titular' => 'nombre en la tarjeta', 'tarjeta_numero' => 'número de la tarjeta',
            'tarjeta_vencimiento' => 'vencimiento', 'tarjeta_cvv' => 'CVV',
        ]);

        if ($empresa->licencias()->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages(['id_plan' => 'Ya hay una renovación esperando aprobación.']);
        }

        $plan = Plan::where('activo', 1)->where('es_prueba', 0)->findOrFail($datos['id_plan']);

        // Nunca se guarda el número completo ni el CVV: solo la marca y los últimos 4 dígitos.
        $pago = PagoSimulado::cobrar(
            $datos['tarjeta_numero'], $datos['tarjeta_titular'],
            $datos['tarjeta_vencimiento'], $datos['tarjeta_cvv'], $plan->precio
        );

        $licencia = Licencias::renovar($empresa, $plan, $pago);
        $enviado = Licencias::notificarPagoRecibido($licencia);

        return redirect()->route('admin.renovacion.index')->with('success',
            'Pago recibido. ' . ($enviado
                ? 'Te enviamos un correo de confirmación; tus credenciales siguen siendo las mismas.'
                : 'No pudimos enviar el correo de confirmación, pero tu pago quedó registrado.')
            . ' Cuando VisiOptica acepte la renovación podrás entrar con normalidad.');
    }
}
