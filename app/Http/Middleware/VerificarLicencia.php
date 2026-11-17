<?php

namespace App\Http\Middleware;

// Licencias: tiene adminPuedeEntrar() y puedeIngresar().
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
// Alias "licencia". Revisa la licencia de la óptica en cada ruta /admin.
class VerificarLicencia
{
    // Dos preguntas distintas: ¿puede estar dentro? y ¿puede usar el panel?
    public function handle(Request $request, Closure $next): Response
    {
        // Usuario logueado.
        $user = Auth::user();
        // Su óptica. "?->" evita error si $user es null.
        $empresa = $user?->empresa;

        // Pregunta 1: la óptica debe existir y no estar suspendida ni pendiente de primer pago.
        if (! Licencias::adminPuedeEntrar($empresa)) {
            // Si no puede, cierra la sesión y lo manda al login explicando el motivo.
            return \App\Http\Controllers\Auth\LoginController::cerrarPorLicencia($request, $user);
        }

        // Pregunta 2: ¿hay licencia vigente? Si no la hay y NO está ya en la pantalla de renovación...
        if (! Licencias::puedeIngresar($empresa) && ! $request->routeIs('admin.renovacion.*')) {
            // ...lo manda a renovar. La excepción evita un bucle infinito de redirecciones.
            return redirect()->route('admin.renovacion.index');
        }

        // Tiene licencia vigente: continúa al panel.
        return $next($request);
    }
}
