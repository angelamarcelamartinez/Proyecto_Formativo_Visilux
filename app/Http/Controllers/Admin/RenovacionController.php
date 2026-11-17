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
// Renovación de licencia desde el panel del administrador. Rutas: /admin/renovar-licencia.
class RenovacionController extends Controller
{
    // GET: muestra la pantalla de renovación.
    public function index(Request $request): View
    {
        // Actualiza los estados antes de mostrar.
        Licencias::marcarVencidas();

        // La óptica del administrador logueado (así nunca ve datos de otra).
        $empresa = Auth::user()->empresa;
        // Si la óptica está suspendida o pendiente, 403 (prohibido).
        abort_unless(Licencias::adminPuedeEntrar($empresa), 403);

        // Su licencia más reciente (para saber si la última solicitud fue rechazada).
        $ultima = $empresa->licencias()->with('plan')->orderByDesc('fecha_solicitud')->orderByDesc('id_licencia')->first();

        // Envía a la vista: si está bloqueada, si hay renovación pendiente, si fue rechazada, desde cuándo venció y los planes disponibles.
        return view('admin.renovacion.index', [
            'empresa' => $empresa,
            // bloqueada = true si ya no tiene licencia vigente.
            'bloqueada' => ! Licencias::puedeIngresar($empresa),
            // Renovación esperando aprobación del superadmin (si existe).
            'pendiente' => $empresa->licencias()->with('plan')->where('estado', 'pendiente')->first(),
            'rechazada' => $ultima && $ultima->estado === 'cancelada' && $ultima->observaciones !== 'Cancelada por la óptica' ? $ultima : null,
            'vencioEl' => $empresa->licencias()->whereIn('estado', ['vencida', 'inactiva', 'activa'])->max('fecha_fin'),
            // Planes activos, menos el de prueba gratis, de menor a mayor duración.
            'planes' => Plan::where('activo', 1)->where('es_prueba', 0)->orderBy('meses')->get(),
            'planElegido' => (int) $request->query('plan'),
            'pageTitle' => 'Renovar licencia ·',
        ]);
    }

    // POST: recibe el formulario de pago de la renovación.
    public function store(Request $request): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        // Misma protección.
        abort_unless(Licencias::adminPuedeEntrar($empresa), 403);

        // Valida plan existente y los 4 datos de la tarjeta. El tercer parámetro da nombres bonitos a los campos.
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

        // Solo se permite una renovación pendiente a la vez.
        if ($empresa->licencias()->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages(['id_plan' => 'Ya hay una renovación esperando aprobación.']);
        }

        // Busca el plan elegido: debe estar activo y no ser el de prueba.
        $plan = Plan::where('activo', 1)->where('es_prueba', 0)->findOrFail($datos['id_plan']);

        // Nunca se guarda el número completo ni el CVV.
        // Nunca se guarda el número completo ni el CVV: solo la marca y los últimos 4 dígitos.
        // Valida la tarjeta (16 dígitos, vencimiento, CVV) y devuelve referencia SIM-..., marca, últimos 4 y titular.
        $pago = PagoSimulado::cobrar(
            $datos['tarjeta_numero'], $datos['tarjeta_titular'],
            $datos['tarjeta_vencimiento'], $datos['tarjeta_cvv'], $plan->precio
        );

        // Crea una licencia NUEVA en estado "pendiente" con esos datos de pago.
        $licencia = Licencias::renovar($empresa, $plan, $pago);
        // Envía el correo "recibimos tu pago" a la óptica y a los administradores.
        $enviado = Licencias::notificarPagoRecibido($licencia);

        // Vuelve a la pantalla de renovación con un mensaje; el mensaje depende de si el correo salió.
        return redirect()->route('admin.renovacion.index')->with('success',
            'Pago recibido. ' . ($enviado
                ? 'Te enviamos un correo de confirmación; tus credenciales siguen siendo las mismas.'
                : 'No pudimos enviar el correo de confirmación, pero tu pago quedó registrado.')
            . ' Cuando VisiOptica acepte la renovación podrás entrar con normalidad.');
    }
}
