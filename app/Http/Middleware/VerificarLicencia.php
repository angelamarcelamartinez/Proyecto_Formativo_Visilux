<?php

namespace App\Http\Middleware;

use App\Support\Licencias;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antes de dejar entrar al panel /admin revisa la óptica del administrador:
 *  - si no existe, está suspendida o espera su primer pago: cierra la sesión y lo devuelve al login;
 *  - si la licencia venció: lo deja ENTRAR, pero solo a la pantalla de renovación (que se le pone
 *    encima del panel). Cuando el superadmin acepta la renovación, todo vuelve a la normalidad.
 */
class VerificarLicencia
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $empresa = $user?->empresa;

        if (! Licencias::adminPuedeEntrar($empresa)) {
            return \App\Http\Controllers\Auth\LoginController::cerrarPorLicencia($request, $user);
        }

        if (! Licencias::puedeIngresar($empresa) && ! $request->routeIs('admin.renovacion.*')) {
            return redirect()->route('admin.renovacion.index');
        }

        return $next($request);
    }
}
