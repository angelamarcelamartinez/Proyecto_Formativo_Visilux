<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias "optometra". Solo deja pasar a usuarios con rol Optómetra (config visioptica.rol_optometra)
 * Esa persona inició sesión con su cuenta de la tabla `optometra` (ver App\Auth\DosTablasUserProvider).
 */
class SoloOptometra
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Otro rol (admin, recepcionista, cliente...) vuelve a SU panel, igual que el middleware "rol".
        if (! $user->esOptometra()) {
            return redirect($user->rutaInicio());
        }

        // El optómetra inicia sesión con su propia cuenta de la tabla `optometra` (App\Models\Optometra).
        // Un registro viejo de `usuario` con rol 7 (antes de 2026_11_25_optometra_cuenta_propia.sql) no entra.
        if (! $user instanceof \App\Models\Optometra) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta de optómetra debe pasarse a la tabla de optómetras. '
                . 'Pide al administrador que ejecute el script 2026_11_25_optometra_cuenta_propia.sql.']);
        }

        return $next($request);
    }
}
