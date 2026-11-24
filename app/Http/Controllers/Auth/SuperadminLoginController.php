<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login exclusivo del superadmin, en una URL secreta que no está enlazada
 * en ninguna parte del sitio (ver config/visioptica.php).
 * Solo deja entrar a usuarios con el rol de superadmin.
 */
// Login separado del superadmin. Su URL secreta sale de SUPERADMIN_LOGIN_PATH en el .env (config/visioptica.php).
class SuperadminLoginController extends Controller
{
    // GET a la URL secreta: muestra el formulario.
    public function showLoginForm()
    {
        // Si ya inició sesión como superadmin, lo manda directo a su panel.
        if (Auth::check() && Auth::user()->esSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        // Muestra la vista superadmin/auth/login.blade.php...
        return response()
            ->view('superadmin.auth.login')
            // ...con una cabecera que pide a Google no indexar esta página.
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    // POST a la URL secreta: intenta iniciar sesión.
    public function login(Request $request)
    {
        // Valida: correo con formato de correo y contraseña obligatorios.
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Clave del limitador de intentos: correo en minúsculas + IP del visitante.
        $clave = Str::lower($credenciales['email']) . '|' . $request->ip();

        // ¿Ya falló 4 veces? Entonces ni siquiera intenta.
        if (RateLimiter::tooManyAttempts($clave, 4)) {
            // Segundos que faltan para poder intentar de nuevo.
            $segundos = RateLimiter::availableIn($clave);
            // Devuelve el error al formulario.
            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos.",
            ]);
        }

        // Auth::attempt compara correo y contraseña (encriptada) con la tabla usuario; si coinciden, inicia la sesión.
        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            // Importante: iniciar sesión no basta, además debe tener rol superadmin.
            if (Auth::user()->esSuperadmin()) {
                // Entró bien: reinicia el contador de intentos.
                RateLimiter::clear($clave);
                // Cambia el identificador de sesión (evita el robo de sesión).
                $request->session()->regenerate();

                // Bitácora de accesos (el trigger trg_log_acceso copia este cambio a log_acceso).
                Auth::user()->registrarIngreso();

                // Va a la página que quería abrir, o al panel del superadmin.
                return redirect()->intended(route('superadmin.dashboard'));
            }

            // Un usuario que no es superadmin no entra por aquí (ni se le dice que la cuenta existe).
            // Si NO es superadmin: cierra la sesión de inmediato...
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Suma un intento fallido; el contador dura 60 segundos.
        RateLimiter::hit($clave, 60);

        throw ValidationException::withMessages([
            // Mensaje genérico (no revela si el correo existe o si es un usuario normal).
            'email' => 'Estas credenciales no coinciden con nuestros registros.',
        ]);
    }
}
