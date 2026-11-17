<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias "rol": deja pasar solo a los roles indicados. Ejemplo: ->middleware('rol:4') (recepcionista)
 * o ->middleware('rol:7') (optómetra). Quien tenga otro rol vuelve a SU propio panel
 * (el administrador al /admin, la recepcionista al /recepcion...), sin cerrarle la sesión.
 */
class SoloRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array((string) $user->id_rol, $roles, true)) {
            $destino = $user->rutaInicio();

            // Un cliente no tiene panel: vuelve al sitio público.
            return redirect($destino);
        }

        return $next($request);
    }
}
