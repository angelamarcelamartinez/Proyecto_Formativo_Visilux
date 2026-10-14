<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\CarritoController;
use App\Http\Controllers\Controller;
use App\Support\Licencias;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /** Intentos fallidos permitidos antes de bloquear el ingreso (1 minuto). */
    protected $maxAttempts = 4;

    /**
     * A dónde redirigir según el rol del usuario autenticado:
     * el superadmin va a /superadmin, los administradores de cada
     * óptica a /admin y el resto se queda en el sitio público (/).
     */
    protected function redirectTo(): string
    {
        $user = auth()->user();

        if ($user && $user->esSuperadmin()) {
            return '/superadmin';
        }

        // Contraseña temporal: primero debe crear la suya.
        if ($user && $user->debe_cambiar_password) {
            return '/cambiar-password';
        }

        return ($user && $user->esAdministrador()) ? '/admin' : '/';
    }

    /**
     * El superadmin NO entra por este login público: tiene el suyo, en una URL
     * secreta. Si alguien prueba sus credenciales aquí, el mensaje es el mismo
     * que el de una contraseña incorrecta.
     */
    protected function attemptLogin(Request $request)
    {
        if (! $this->guard()->attempt($this->credentials($request), $request->boolean('remember'))) {
            return false;
        }

        if ($this->guard()->user()->esSuperadmin()) {
            $this->guard()->logout();

            return false;
        }

        return true;
    }

    /**
     * Laravel llama esto automáticamente justo después de un login
     * exitoso, antes de redirigir. Aquí fusionamos lo que la persona
     * haya agregado al carrito como invitada con su carrito real,
     * para que no pierda nada al iniciar sesión.
     */
    protected function authenticated(Request $request, $user)
    {
        // Cuenta desactivada: el superadmin desactiva a los administradores y cada administrador
        // desactiva a los demás usuarios de su óptica.
        if (in_array((int) $user->id_estado, [2, 4], true)) {
            return self::cerrarSesionCon($request, $user->esAdministrador()
                ? 'Tu cuenta de administrador está inactiva. Comunícate con VisiOptica para activarla.'
                : 'Tu cuenta está inactiva. Pide al administrador de tu óptica que la active.');
        }

        $empresa = $user->empresa;

        if ($user->esAdministrador()) {
            // El administrador entra aunque la licencia haya vencido: verá la pantalla para renovarla.
            // Solo se le cierra la puerta si la óptica no existe, está suspendida o espera su primer pago.
            if (! Licencias::adminPuedeEntrar($empresa)) {
                return self::cerrarPorLicencia($request, $user);
            }
        } elseif ($empresa && ! Licencias::puedeIngresar($empresa)) {
            // Clientes, recepcionistas, asesores...: no entran mientras la licencia esté inactiva.
            return self::cerrarPorLicenciaInactiva($request, $user);
        }

        app(CarritoController::class)->fusionarCarritoInvitado($user->documento);
    }

    /**
     * Mensaje para los usuarios que no son administradores cuando la licencia de su óptica no está activa.
     */
    public static function cerrarPorLicenciaInactiva(Request $request, $user)
    {
        $nombre = $user->empresa?->nombre ?? 'tu óptica';

        return self::cerrarSesionCon($request, "La licencia de {$nombre} está inactiva, por eso no puedes ingresar por ahora. "
            . 'Comunícate con el administrador de la óptica.');
    }

    /** Cierra la sesión y vuelve al login con un mensaje. */
    public static function cerrarSesionCon(Request $request, string $mensaje, ?string $renovarNit = null)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('licencia_bloqueada', [
            'mensaje' => $mensaje,
            'renovar_nit' => $renovarNit,
        ]);
    }

    /**
     * Cierra la sesión y vuelve al login explicando por qué no puede entrar.
     * También la usa el middleware VerificarLicencia para sesiones que ya estaban abiertas.
     */
    public static function cerrarPorLicencia(Request $request, $user)
    {
        $empresa = $user->empresa;

        if (! $empresa) {
            $mensaje = 'Tu usuario no está asociado a ninguna óptica. Comunícate con VisiOptica.';
        } elseif ($empresa->estaSuspendida()) {
            $mensaje = "La cuenta de {$empresa->nombre} está suspendida. Comunícate con VisiOptica para reactivarla.";
        } elseif ($empresa->estaPendiente()) {
            $mensaje = "El registro de {$empresa->nombre} todavía está esperando la aprobación del pago.";
        } else {
            $fin = $empresa->licencias()->whereIn('estado', ['activa', 'vencida'])->max('fecha_fin');
            $mensaje = "La licencia de {$empresa->nombre} venció"
                . ($fin ? ' el ' . \Illuminate\Support\Carbon::parse($fin)->format('d/m/Y') : '')
                . '. Renueva tu plan para volver a entrar; tus datos siguen guardados.';
        }

        // Solo se ofrece renovar cuando lo que falta es pagar.
        return self::cerrarSesionCon($request, $mensaje, ($empresa && $empresa->estado === 'activa') ? $empresa->nit : null);
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
