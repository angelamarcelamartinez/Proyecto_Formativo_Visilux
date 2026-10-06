<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Plan;
use App\Models\Usuario;
use App\Support\Licencias;
use App\Support\PagoSimulado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Renovación pública: la óptica cuya licencia venció ya no puede entrar al panel,
 * así que renueva desde aquí con su NIT y el correo de un administrador.
 * El pago (simulado) queda pendiente hasta que el superadmin lo apruebe.
 */
class RenovarController extends Controller
{
    public function create(Request $request): View
    {
        return view('renovar.create', [
            'planes' => Plan::where('activo', 1)->where('es_prueba', 0)->orderBy('meses')->get(),
            'nit' => $request->query('nit'),
            'pageTitle' => 'Renovar plan ·',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nit' => ['required', 'string', 'max:20'],
            'admin_email' => ['required', 'email', 'max:100'],
            'id_plan' => ['required', 'exists:plan,id_plan'],
            'tarjeta_titular' => ['required', 'string', 'max:100'],
            'tarjeta_numero' => ['required', 'string', 'max:23'],
            'tarjeta_vencimiento' => ['required', 'string', 'max:5'],
            'tarjeta_cvv' => ['required', 'string', 'max:4'],
        ], [], [
            'nit' => 'NIT', 'admin_email' => 'correo del administrador', 'id_plan' => 'plan',
            'tarjeta_titular' => 'nombre en la tarjeta', 'tarjeta_numero' => 'número de la tarjeta',
            'tarjeta_vencimiento' => 'vencimiento', 'tarjeta_cvv' => 'CVV',
        ]);

        // El NIT y el correo tienen que corresponder a un administrador de esa óptica.
        $empresa = Empresa::find(trim($datos['nit']));
        $esAdmin = $empresa && Usuario::where('nit_empresa', $empresa->nit)
            ->where('id_rol', config('visioptica.rol_admin'))
            ->where('email', trim($datos['admin_email']))
            ->exists();

        if (! $esAdmin) {
            throw ValidationException::withMessages([
                'nit' => 'No encontramos una óptica con ese NIT y ese correo de administrador.',
            ]);
        }

        if ($empresa->estaSuspendida()) {
            throw ValidationException::withMessages(['nit' => 'Esta óptica está suspendida. Comunícate con VisiOptica para reactivarla.']);
        }
        if ($empresa->estaPendiente()) {
            throw ValidationException::withMessages(['nit' => 'El registro de esta óptica todavía espera la aprobación de su primer pago.']);
        }
        if ($empresa->licencias()->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages(['nit' => 'Ya hay una renovación de esta óptica esperando aprobación. Te avisaremos por correo.']);
        }

        $plan = Plan::where('activo', 1)->where('es_prueba', 0)->findOrFail($datos['id_plan']);

        $pago = PagoSimulado::cobrar(
            $datos['tarjeta_numero'], $datos['tarjeta_titular'],
            $datos['tarjeta_vencimiento'], $datos['tarjeta_cvv'], $plan->precio
        );

        $licencia = Licencia::create([
            'nit_empresa' => $empresa->nit,
            'id_plan' => $plan->id_plan,
            'estado' => 'pendiente',
            'valor' => $plan->precio,
            'referencia_pago' => $pago['referencia'],
            'observaciones' => "Renovación con pago simulado: {$pago['marca']} terminada en {$pago['ultimos4']} a nombre de {$pago['titular']}",
            'fecha_solicitud' => now(),
        ]);

        Actividad::registrar($empresa->nit, 'licencia', "Pagó la renovación del plan {$plan->nombre}", 'licencia', $licencia->id_licencia);

        $inicio = Licencias::fechaInicioPara($empresa);

        return redirect()->route('renovar.create')->with('renovacion', [
            'empresa' => $empresa->nombre,
            'plan' => $plan->nombre,
            'valor' => $plan->precioFormateado(),
            'inicio' => $inicio->format('d/m/Y'),
            'pago' => $pago,
        ]);
    }
}
