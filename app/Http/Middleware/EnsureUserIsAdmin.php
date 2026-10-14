<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo deja pasar a usuarios con rol "Administrador" (id_rol = 1).
 * El login ya filtra esto, pero se revalida en cada request como
 * segunda capa de seguridad.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // El superadmin tiene su propio panel.
        if ($user && $user->esSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        if (! $user || (int) $user->id_rol !== 1) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['correo' => 'Tu cuenta no tiene permisos de administrador.']);
        }

        return $next($request);
    }
}
