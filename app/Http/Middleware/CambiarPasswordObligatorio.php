<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el usuario entró con la contraseña temporal que le generó el sistema, no lo deja
 * usar el panel hasta que la cambie (solo puede ver la pantalla de cambio y cerrar sesión).
 */
class CambiarPasswordObligatorio
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->debe_cambiar_password
            && ! $request->routeIs('password.cambiar', 'password.cambiar.guardar', 'logout')) {
            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}
