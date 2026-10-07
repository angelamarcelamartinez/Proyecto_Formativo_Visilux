<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Support\CamaraComercio;
use App\Support\PagoSimulado;
use App\Support\RegistroOptica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Una óptica nueva elige un plan en /planes, llena sus datos, "paga" con tarjeta
 * (simulado) y queda esperando que el superadmin apruebe el pago.
 */
class ContratarController extends Controller
{
    public function create(Plan $plan): View
    {
        abort_unless($plan->activo, 404);

        return view('contratar.create', [
            'plan' => $plan,
            'pageTitle' => 'Contratar ' . $plan->nombre . ' ·',
        ]);
    }

    public function store(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->activo, 404);

        $reglasPago = $plan->precio > 0 ? [
            'tarjeta_titular' => ['required', 'string', 'max:100'],
            'tarjeta_numero' => ['required', 'string', 'max:23'],
            'tarjeta_vencimiento' => ['required', 'string', 'max:5'],
            'tarjeta_cvv' => ['required', 'string', 'max:4'],
        ] : [];

        $datos = $request->validate(array_merge([
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:20', 'regex:/^[0-9-]+$/', 'unique:empresa,nit'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'ciudad' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'admin_documento' => ['required', 'integer', 'min:1', 'max:2147483647', 'unique:usuario,documento'],
            'admin_nombres' => ['required', 'string', 'max:100'],
            'admin_apellido' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:100', 'unique:usuario,email'],
            // En la tabla usuario el teléfono es único, por eso se valida aparte del de la óptica.
            'admin_telefono' => ['required', 'string', 'max:20', 'unique:usuario,telefono'],
            'acepto' => ['accepted'],
            // Certificado de existencia y representación legal (Cámara de Comercio)
            'camara_comercio' => CamaraComercio::reglas(),
        ], $reglasPago), array_merge(CamaraComercio::mensajes(), [
            'nit.unique' => 'Ya hay una óptica registrada con ese NIT.',
            'admin_email.unique' => 'Ese correo ya tiene una cuenta. Usa otro para el administrador.',
            'admin_documento.unique' => 'Ya existe un usuario con ese documento.',
            'admin_telefono.unique' => 'Ya existe un usuario con ese celular.',
            'acepto.accepted' => 'Debes aceptar los términos para continuar.',
        ]), [
            'nombre' => 'nombre de la óptica', 'nit' => 'NIT', 'email' => 'correo de la óptica',
            'admin_documento' => 'documento', 'admin_nombres' => 'nombres', 'admin_apellido' => 'apellidos',
            'admin_email' => 'correo del administrador', 'admin_telefono' => 'celular', 'tarjeta_titular' => 'nombre en la tarjeta',
            'tarjeta_numero' => 'número de la tarjeta', 'tarjeta_vencimiento' => 'vencimiento', 'tarjeta_cvv' => 'CVV',
            'camara_comercio' => 'certificado de la Cámara de Comercio',
        ]);

        // Primero se "cobra"; si la tarjeta falla no se crea nada.
        $pago = $plan->precio > 0
            ? PagoSimulado::cobrar(
                $datos['tarjeta_numero'], $datos['tarjeta_titular'],
                $datos['tarjeta_vencimiento'], $datos['tarjeta_cvv'], $plan->precio
            )
            : null;

        // El PDF se guarda en una carpeta privada; si algo falla al registrar, se borra.
        $rutaCamara = CamaraComercio::guardar($request->file('camara_comercio'), $datos['nit']);

        try {
            $empresa = RegistroOptica::registrar($datos, $plan, $pago, $rutaCamara);
        } catch (\Throwable $e) {
            CamaraComercio::borrar($rutaCamara);
            throw $e;
        }

        return redirect()->route('contratar.enviado')->with('registro', [
            'empresa' => $empresa->nombre,
            'plan' => $plan->nombre,
            'valor' => $plan->precioFormateado(),
            'correo' => $datos['email'],
            'usuario' => $datos['admin_email'],
            'pago' => $pago,
        ]);
    }

    public function enviado(): View|RedirectResponse
    {
        if (! session('registro')) {
            return redirect()->route('planes');
        }

        return view('contratar.enviado', [
            'registro' => session('registro'),
            'pageTitle' => 'Solicitud enviada ·',
        ]);
    }
}
