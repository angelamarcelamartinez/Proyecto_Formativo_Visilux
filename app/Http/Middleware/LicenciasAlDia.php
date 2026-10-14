<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\LoginController;
use App\Support\Licencias;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corre en todas las páginas del sitio:
 *  1. Pasa a «vencida» (o «inactiva») las licencias según la fecha del sistema, sin depender del cron.
 *  2. Si quien tiene la sesión abierta NO es administrador (cliente, recepcionista, asesor...) y la licencia
 *     de su óptica ya no está activa, le cierra la sesión con el aviso de «licencia inactiva».
 */
class LicenciasAlDia
{
    public function handle(Request $request, Closure $next): Response
    {
        Licencias::marcarVencidasAutomatico();

        $user = Auth::user();

        if ($user && ! $user->esAdministrador() && ! $user->esSuperadmin() && $user->nit_empresa
            && ! $request->routeIs('logout')
            && ! Licencias::puedeIngresar($user->empresa)) {
            return LoginController::cerrarPorLicenciaInactiva($request, $user);
        }

        // Contraseña temporal sin cambiar (administradores y empleados): solo pueden ver la pantalla de cambio.
        if ($user && $user->debe_cambiar_password
            && ! $request->routeIs('password.cambiar', 'password.cambiar.guardar', 'logout')) {
            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}
