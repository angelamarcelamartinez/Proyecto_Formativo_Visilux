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
// Alias "admin". Solo deja pasar a los administradores de óptica (id_rol 1).
class EnsureUserIsAdmin
{
    // Se ejecuta antes de cada ruta del grupo /admin.
    public function handle(Request $request, Closure $next): Response
    {
        // Usuario logueado.
        $user = Auth::user();

        // Aclaración: el superadmin no usa /admin.
        // El superadmin tiene su propio panel.
        // Si es superadmin, lo manda a su propio panel...
        if ($user && $user->esSuperadmin()) {
            // ...en vez de mostrarle el panel de una óptica.
            return redirect()->route('superadmin.dashboard');
        }

        // Si no es administrador (rol distinto de 1)...
        if (! $user || (int) $user->id_rol !== 1) {
            // ...cierra su sesión,
            Auth::logout();
            // destruye la sesión actual (evita reutilizarla)
            $request->session()->invalidate();
            // y genera un token CSRF nuevo.
            $request->session()->regenerateToken();

            // Lo manda al login con un mensaje de error.
            return redirect()->route('login')
                ->withErrors(['correo' => 'Tu cuenta no tiene permisos de administrador.']);
        }

        // Es administrador: continúa.
        return $next($request);
    }
}
