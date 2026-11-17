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

    // Trait de Laravel con el login ya hecho (login(), logout(), etc.). Este controlador personaliza partes.
    use AuthenticatesUsers;

    /** Intentos fallidos permitidos antes de bloquear el ingreso (1 minuto). */
    // Límite del limitador de Laravel (4 intentos).
    protected $maxAttempts = 4;
    // Texto que se muestra cuando la cuenta está inactiva.
    public const MENSAJE_INACTIVO = 'Tu cuenta está inactiva. Comunícate con el administrador de la óptica para que la active.';

    /** Usuario al que corresponde el correo del intento actual (null si no existe). */
    // Guarda al usuario del correo escrito para contar sus fallos.
    protected ?Usuario $usuarioDelIntento = null;

    /**
     * A dónde redirigir según el rol del usuario autenticado:
     * el superadmin va a /superadmin, los administradores de cada
     * óptica a /admin y el resto se queda en el sitio público (/).
     */
    // A dónde ir después de iniciar sesión según el rol.
    protected function redirectTo(): string
    {
        // Usuario recién logueado.
        $user = auth()->user();

        // Superadmin → /superadmin.
        if ($user && $user->esSuperadmin()) {
            return '/superadmin';
        }

        // Contraseña temporal: primero debe crear la suya.
        // Contraseña temporal → primero /cambiar-password.
        if ($user && $user->debe_cambiar_password) {
            return '/cambiar-password';
        }

        // Administrador → /admin; los demás → /.
        return ($user && $user->esAdministrador()) ? '/admin' : '/';
    }

    /**
     * El superadmin NO entra por este login público: tiene el suyo, en una URL
     * secreta. Si alguien prueba sus credenciales aquí, el mensaje es el mismo
     * que el de una contraseña incorrecta.
     */
    // Se ejecuta cuando se envía el formulario: aquí se decide si las credenciales sirven.
    protected function attemptLogin(Request $request)
    {
        // Usuario dueño del correo escrito (si existe), para contar sus intentos fallidos.
        // Busca al dueño del correo escrito (si existe).
        $this->usuarioDelIntento = Usuario::where('email', trim((string) $request->input($this->username())))->first();
        // Atajo.
        $usuario = $this->usuarioDelIntento;

        // Cuenta inactiva: no entra aunque la contraseña sea correcta.
        // Si la cuenta está inactiva (id_estado 2) no entra, aunque la contraseña sea correcta.
        if ($usuario && ! $usuario->esSuperadmin() && BloqueoUsuarios::estaInactivo($usuario)) {
            // Deja listo el aviso emergente "tu cuenta está inactivada".
            $this->prepararVentanaReactivacion($request, $usuario);

            throw ValidationException::withMessages([
                $this->username() => [self::MENSAJE_INACTIVO],
            ]);
        }

        // Compara correo y contraseña con la base de datos. Si no coinciden devuelve false (Laravel llamará a sendFailedLoginResponse).
        if (! $this->guard()->attempt($this->credentials($request), $request->boolean('remember'))) {
            return false;
        }

        // El superadmin NO puede entrar por este login: se cierra la sesión y se da el mismo error de siempre.
        if ($this->guard()->user()->esSuperadmin()) {
            $this->guard()->logout();

            return false;
        }

        // Entró bien: se reinician sus intentos fallidos.
        // Entró bien: se reinician los intentos fallidos.
        BloqueoUsuarios::limpiar($this->guard()->user());

        return true;
    }

    /**
     * Contraseña incorrecta: suma un intento fallido al usuario de ese correo.
     * Con 3 fallos seguidos y un 4.º también fallido, la cuenta queda inactiva
     * hasta que un administrador la active desde el panel.
     */
    // Contraseña incorrecta: suma un intento fallido.
    protected function sendFailedLoginResponse(Request $request)
    {
        // Usuario del correo escrito.
        $usuario = $this->usuarioDelIntento;

        // Correo inexistente o superadmin: solo el mensaje genérico, sin contar intentos.
        if (! $usuario || $usuario->esSuperadmin()) {
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed')],
            ]);
        }

        // Suma el intento. Devuelve cuántos quedan (0 = la cuenta acaba de quedar inactiva).
        $quedan = BloqueoUsuarios::registrarFallo($usuario);

        // Al 4.º fallo: aviso de cuenta inactivada.
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
    // Laravel la llama justo después de un login correcto.
    protected function authenticated(Request $request, $user)
    {
        // Cuenta desactivada: el superadmin desactiva a los administradores y cada administrador
        // desactiva a los demás usuarios de su óptica.
        // Si su cuenta está inactiva (2 o 4), cierra la sesión.
        if (in_array((int) $user->id_estado, [2, 4], true)) {
            return self::cerrarSesionCon($request, $user->esAdministrador()
                ? 'Tu cuenta de administrador está inactiva. Comunícate con VisiOptica para activarla.'
                : 'Tu cuenta está inactiva. Pide al administrador de tu óptica que la active.');
        }

        // Su óptica.
        $empresa = $user->empresa;

        // El administrador entra aunque la licencia esté vencida (verá la pantalla de renovación).
        if ($user->esAdministrador()) {
            // El administrador entra aunque la licencia haya vencido: verá la pantalla para renovarla.
            // Solo se le cierra la puerta si la óptica no existe, está suspendida o espera su primer pago.
            // Solo se le cierra la puerta si la óptica está suspendida, pendiente o no existe.
            if (! Licencias::adminPuedeEntrar($empresa)) {
                return self::cerrarPorLicencia($request, $user);
            }
        // Empleados y clientes: no entran si la licencia no está vigente.
        } elseif ($empresa && ! Licencias::puedeIngresar($empresa)) {
            // Clientes, recepcionistas, asesores...: no entran mientras la licencia esté inactiva.
            return self::cerrarPorLicenciaInactiva($request, $user);
        }

        // Une el carrito que tenía como invitado con el de su cuenta.
        app(CarritoController::class)->fusionarCarritoInvitado($user->documento);
    }

    /**
     * Mensaje para los usuarios que no son administradores cuando la licencia de su óptica no está activa.
     */
    // Mensaje para empleados/clientes cuando la licencia de su óptica no está vigente.
    public static function cerrarPorLicenciaInactiva(Request $request, $user)
    {
        $nombre = $user->empresa?->nombre ?? 'tu óptica';

        return self::cerrarSesionCon($request, "La licencia de {$nombre} está inactiva, por eso no puedes ingresar por ahora. "
            . 'Comunícate con el administrador de la óptica.');
    }

    /** Cierra la sesión y vuelve al login con un mensaje. */
    // Cierra la sesión (logout + invalidar sesión + nuevo token) y vuelve al login con un mensaje.
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
    // Elige el mensaje según el motivo: sin óptica, suspendida, pendiente de aprobación o licencia vencida.
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

        // Si la óptica está activa, lo único que falta es pagar: se ofrece renovar.
        // Solo se ofrece renovar cuando lo que falta es pagar.
        return self::cerrarSesionCon($request, $mensaje, ($empresa && $empresa->estado === 'activa') ? $empresa->nit : null);
    }

    /**
     * Validación en línea: ¿el correo pertenece a un usuario registrado?
     * El superadmin se trata como "no registrado" porque no entra por este login.
     */
    // Validación en línea del login: devuelve JSON {existe: true/false}. El superadmin cuenta como "no existe".
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
    // Guarda en la sesión el documento para la ventana emergente "cuenta inactivada".
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
    // Antes enviaba la solicitud de activación al administrador (función heredada del flujo anterior).
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
    // Middlewares: "guest" para todos (excepto logout) y "auth" solo para logout.
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
