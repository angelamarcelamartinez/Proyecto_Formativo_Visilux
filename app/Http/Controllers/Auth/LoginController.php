<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\CarritoController;
use App\Http\Controllers\Controller;
use App\Support\Licencias;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Support\BloqueoUsuarios;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    public const MENSAJE_INACTIVO = 'Tu cuenta está inactiva. Comunícate con el administrador de la óptica para que la active.';

    /** Usuario al que corresponde el correo del intento actual (null si no existe). */
    protected ?Usuario $usuarioDelIntento = null;

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
        // Usuario dueño del correo escrito (si existe), para contar sus intentos fallidos.
        $this->usuarioDelIntento = Usuario::where('email', trim((string) $request->input($this->username())))->first();
        $usuario = $this->usuarioDelIntento;

        // Cuenta inactiva: no entra aunque la contraseña sea correcta.
        if ($usuario && ! $usuario->esSuperadmin() && BloqueoUsuarios::estaInactivo($usuario)) {
            $this->prepararVentanaReactivacion($request, $usuario);

            throw ValidationException::withMessages([
                $this->username() => [self::MENSAJE_INACTIVO],
            ]);
        }

        if (! $this->guard()->attempt($this->credentials($request), $request->boolean('remember'))) {
            return false;
        }

        if ($this->guard()->user()->esSuperadmin()) {
            $this->guard()->logout();

            return false;
        }

        // Entró bien: se reinician sus intentos fallidos.
        BloqueoUsuarios::limpiar($this->guard()->user());

        return true;
    }

    /**
     * Contraseña incorrecta: suma un intento fallido al usuario de ese correo.
     * Con 3 fallos seguidos y un 4.º también fallido, la cuenta queda inactiva
     * hasta que un administrador la active desde el panel.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $usuario = $this->usuarioDelIntento;

        if (! $usuario || $usuario->esSuperadmin()) {
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed')],
            ]);
        }

        $quedan = BloqueoUsuarios::registrarFallo($usuario);

        if ($quedan === 0) {
            $this->prepararVentanaReactivacion($request, $usuario);

            throw ValidationException::withMessages([
                $this->username() => ['Tu cuenta fue inactivada por ' . BloqueoUsuarios::MAX_INTENTOS
                    . ' intentos fallidos. Comunícate con el administrador de la óptica para que la active.'],
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed') . ' Te '
                . ($quedan === 1 ? 'queda 1 intento' : "quedan {$quedan} intentos")
                . ' antes de que tu cuenta sea inactivada.'],
        ]);
    }

    /**
     * Laravel llama esto automáticamente justo después de un login
     * exitoso, antes de redirigir. Aquí fusionamos lo que la persona
     * haya agregado al carrito como invitada con su carrito real,
     * para que no pierda nada al iniciar sesión.
     */
    protected function authenticated(Request $request, $user)
    {
        // Un administrador de óptica solo entra si su óptica está activa y con licencia vigente.
        if ($user->esAdministrador() && ! Licencias::puedeIngresar($user->empresa)) {
            return self::cerrarPorLicencia($request, $user);
        }

        app(CarritoController::class)->fusionarCarritoInvitado($user->documento);
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

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('licencia_bloqueada', [
            'mensaje' => $mensaje,
            // Solo se ofrece renovar cuando lo que falta es pagar.
            'renovar_nit' => ($empresa && $empresa->estado === 'activa') ? $empresa->nit : null,
        ]);
    }

    /**
     * Validación en línea: ¿el correo pertenece a un usuario registrado?
     * El superadmin se trata como "no registrado" porque no entra por este login.
     */
    public function verificarEmail(Request $request)
    {
        $email = trim((string) $request->input('email'));

        $usuario = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? Usuario::where('email', $email)->first()
            : null;

        return response()->json([
            'existe' => $usuario !== null && ! $usuario->esSuperadmin(),
        ]);
    }

    /**
     * Deja lista la ventana emergente "Tu cuenta ha sido inactivada" del login.
     * El documento queda en la sesión: así la solicitud solo la puede enviar
     * quien acaba de intentar entrar con esa cuenta, no cualquiera con un correo.
     */
    protected function prepararVentanaReactivacion(Request $request, Usuario $usuario): void
    {
        $request->session()->put('reactivacion_documento', $usuario->documento);
        $request->session()->flash('cuenta_inactiva', [
            // A un administrador no lo activa otro administrador, sino VisiOptica.
            'es_admin' => $usuario->esAdministrador(),
            'ya_solicitada' => BloqueoUsuarios::solicitudEnviada($usuario),
        ]);
    }

    /**
     * Botón "Enviar tu solicitud" de la ventana emergente: le envía un correo
     * al administrador de la óptica pidiendo que active la cuenta.
     */
    public function solicitarReactivacion(Request $request)
    {
        $usuario = Usuario::find($request->session()->get('reactivacion_documento'));

        if (! $usuario || $usuario->esSuperadmin() || $usuario->esAdministrador() || ! BloqueoUsuarios::estaInactivo($usuario)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No pudimos enviar la solicitud. Intenta iniciar sesión de nuevo.',
            ], 422);
        }

        return match (BloqueoUsuarios::solicitarReactivacion($usuario)) {
            'enviada' => response()->json(['ok' => true,
                'mensaje' => 'Tu solicitud fue enviada al administrador de la óptica. Te avisaremos por correo cuando tu cuenta esté activa.']),
            'ya_enviada' => response()->json(['ok' => true,
                'mensaje' => 'Ya enviaste una solicitud. El administrador la revisará pronto y te avisaremos por correo.']),
            'sin_destinatario' => response()->json(['ok' => false,
                'mensaje' => 'Tu óptica no tiene un administrador activo. Comunícate directamente con la óptica.'], 422),
            default => response()->json(['ok' => false,
                'mensaje' => 'No pudimos enviar el correo en este momento. Inténtalo de nuevo más tarde.'], 500),
        };
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
