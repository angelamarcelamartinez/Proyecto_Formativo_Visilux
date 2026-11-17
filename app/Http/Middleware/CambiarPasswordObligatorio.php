<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el usuario entró con la contraseña temporal que le generó el sistema, no lo deja
 * usar el panel hasta que la cambie (solo puede ver la pantalla de cambio y cerrar sesión).
 */
// Alias "cambiar.password". Obliga a cambiar la contraseña temporal antes de usar el panel.
class CambiarPasswordObligatorio
{
    // Se ejecuta en cada ruta del grupo /admin.
    public function handle(Request $request, Closure $next): Response
    {
        // Otra forma de obtener el usuario logueado.
        $user = $request->user();

        // debe_cambiar_password es un atributo calculado del modelo Usuario (rol admin/empleado y id_estado 3).
        if ($user && $user->debe_cambiar_password
            // Excepción: puede entrar a la pantalla de cambio, a guardarla y a cerrar sesión (si no, quedaría en un bucle).
            && ! $request->routeIs('password.cambiar', 'password.cambiar.guardar', 'logout')) {
            // Lo manda a /cambiar-password.
            return redirect()->route('password.cambiar');
        }

        // No tiene contraseña temporal: continúa.
        return $next($request);
    }
}
