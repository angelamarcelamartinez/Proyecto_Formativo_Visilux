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
class SuperadminLoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->esSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        return response()
            ->view('superadmin.auth.login')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $clave = Str::lower($credenciales['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $segundos = RateLimiter::availableIn($clave);
            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos.",
            ]);
        }

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            if (Auth::user()->esSuperadmin()) {
                RateLimiter::clear($clave);
                $request->session()->regenerate();

                return redirect()->intended(route('superadmin.dashboard'));
            }

            // Un usuario que no es superadmin no entra por aquí (ni se le dice que la cuenta existe).
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        RateLimiter::hit($clave, 60);

        throw ValidationException::withMessages([
            'email' => 'Estas credenciales no coinciden con nuestros registros.',
        ]);
    }
}
