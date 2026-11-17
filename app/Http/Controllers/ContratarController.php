<?php

// Controlador público (carpeta app/Http/Controllers): lo usa cualquier visitante, no necesita sesión.
namespace App\Http\Controllers;

// Plan: modelo de la tabla "plan" (los planes que se venden).
use App\Models\Plan;
// CamaraComercio: valida y guarda el PDF del certificado.
use App\Support\CamaraComercio;
// Nit: normaliza y valida el NIT (10 dígitos, sin guion).
use App\Support\Nit;
// PagoSimulado: valida la tarjeta y genera la referencia SIM-... (no hay pasarela real).
use App\Support\PagoSimulado;
// RegistroOptica: crea empresa + administrador + licencia pendiente en una transacción.
use App\Support\RegistroOptica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Una óptica nueva elige un plan en /planes, llena sus datos, "paga" con tarjeta
 * (simulado) y queda esperando que el superadmin apruebe el pago.
 */
// Un controlador recibe la petición de una ruta (routes/web.php), usa clases de negocio y devuelve una vista o redirección.
class ContratarController extends Controller
{
    // GET /planes/{plan}/contratar → muestra el formulario. Laravel convierte {plan} en el objeto Plan (route model binding).
    public function create(Plan $plan): View
    {
        // Si el plan está desactivado por el superadmin, responde 404 y no muestra el formulario.
        abort_unless($plan->activo, 404);

        // Muestra resources/views/contratar/create.blade.php enviándole el plan y el título de la pestaña.
        return view('contratar.create', [
            'plan' => $plan,
            'pageTitle' => 'Contratar ' . $plan->nombre . ' ·',
        ]);
    }

    // POST /planes/{plan}/contratar → recibe el formulario completo y registra la óptica.
    public function store(Request $request, Plan $plan): RedirectResponse
    {
        // Se vuelve a comprobar: alguien pudo enviar el formulario con el plan ya desactivado.
        abort_unless($plan->activo, 404);

        // El NIT llega con máscara (800000000-9); se guarda solo con dígitos (8000000009).
        // Cambia el valor del campo "nit" del request: quita guion y puntos antes de validar (800000000-9 → 8000000009).
        $request->merge(['nit' => Nit::normalizar($request->input('nit'))]);

        // Si el plan cuesta algo (precio > 0) se exigen los 4 campos de la tarjeta; si es gratis, no se piden.
        $reglasPago = $plan->precio > 0 ? [
            'tarjeta_titular' => ['required', 'string', 'max:100'],
            'tarjeta_numero' => ['required', 'string', 'max:23'],
            'tarjeta_vencimiento' => ['required', 'string', 'max:5'],
            'tarjeta_cvv' => ['required', 'string', 'max:4'],
        ] : [];

        // validate() revisa todos los campos. Si alguno falla, Laravel vuelve al formulario con los errores y NO sigue ejecutando.
        // Devuelve en $datos solo los campos validados.
        $datos = $request->validate(array_merge([
            'nombre' => ['required', 'string', 'max:150'],
            // NIT: reglas de Nit::reglas() (obligatorio, 10 dígitos) + que no exista ya en la tabla empresa.
            'nit' => array_merge(Nit::reglas(), ['unique:empresa,nit']),
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'ciudad' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            // Documento del administrador: entero único en la tabla usuario (es la llave primaria de usuario).
            'admin_documento' => ['required', 'integer', 'min:1', 'max:2147483647', 'unique:usuario,documento'],
            'admin_nombres' => ['required', 'string', 'max:100'],
            'admin_apellido' => ['required', 'string', 'max:100'],
            // Correo del administrador: único; es el usuario con el que va a iniciar sesión.
            'admin_email' => ['required', 'email', 'max:100', 'unique:usuario,email'],
            // En la tabla usuario el teléfono es único, por eso se valida aparte del de la óptica.
            'admin_telefono' => ['required', 'string', 'max:20', 'unique:usuario,telefono'],
            // Casilla de términos: 'accepted' exige que esté marcada.
            'acepto' => ['accepted'],
            // Certificado de existencia y representación legal (Cámara de Comercio)
            // Archivo PDF de máximo 5 MB (reglas definidas en CamaraComercio::reglas()).
            'camara_comercio' => CamaraComercio::reglas(),
        // Segundo parámetro de validate(): mensajes de error personalizados en español.
        // Tercer parámetro (más abajo): nombres bonitos de los campos para esos mensajes.
        ], $reglasPago), array_merge(CamaraComercio::mensajes(), Nit::mensajes(), [
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
        // PRIMERO se cobra: si la tarjeta es inválida lanza un error y no se crea nada en la base de datos.
        // Si el plan es gratis, $pago queda null.
        $pago = $plan->precio > 0
            ? PagoSimulado::cobrar(
                $datos['tarjeta_numero'], $datos['tarjeta_titular'],
                $datos['tarjeta_vencimiento'], $datos['tarjeta_cvv'], $plan->precio
            )
            : null;

        // El PDF se guarda en una carpeta privada; si algo falla al registrar, se borra.
        // Guarda el PDF en storage/app/private/camara_comercio (carpeta privada) y devuelve la ruta que se guardará en empresa.camara_comercio.
        $rutaCamara = CamaraComercio::guardar($request->file('camara_comercio'), $datos['nit']);

        // try/catch: si el registro falla a mitad de camino, se borra el PDF que ya se había guardado para no dejar archivos huérfanos.
        try {
            // Crea en una transacción: empresa (pendiente), administrador (contraseña basura) y licencia (pendiente).
            $empresa = RegistroOptica::registrar($datos, $plan, $pago, $rutaCamara);
        } catch (\Throwable $e) {
            CamaraComercio::borrar($rutaCamara);
            // Vuelve a lanzar el error original para que se muestre/registre normalmente.
            throw $e;
        }

        // Redirige a la pantalla de confirmación. with() guarda los datos en la sesión solo para la siguiente petición (flash).
        return redirect()->route('contratar.enviado')->with('registro', [
            'empresa' => $empresa->nombre,
            'plan' => $plan->nombre,
            'valor' => $plan->precioFormateado(),
            'correo' => $datos['email'],
            'usuario' => $datos['admin_email'],
            'pago' => $pago,
        ]);
    }

    // GET /planes/solicitud-enviada → pantalla "solicitud enviada, espera la aprobación".
    public function enviado(): View|RedirectResponse
    {
        // Si alguien abre esta URL directamente (sin haber registrado nada) no hay datos en sesión: lo manda a /planes.
        if (! session('registro')) {
            return redirect()->route('planes');
        }

        // Muestra resources/views/contratar/enviado.blade.php con el resumen del registro.
        return view('contratar.enviado', [
            'registro' => session('registro'),
            'pageTitle' => 'Solicitud enviada ·',
        ]);
    }
}
