<?php

namespace App\Http\Middleware;

// LoginController: para reutilizar su función que cierra la sesión.
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
// Middleware GLOBAL (se registra en bootstrap/app.php): corre en TODAS las peticiones web.
class LicenciasAlDia
{
    // Hace tres cosas, en orden.
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Actualiza vencimientos (activa → vencida). Está limitado a una vez cada 5 minutos con Cache.
        Licencias::marcarVencidasAutomatico();

        // Usuario logueado (puede ser null: visitante).
        $user = Auth::user();

        // 2) Si es un empleado o cliente de una óptica (ni admin ni superadmin, y con nit_empresa)...
        if ($user && ! $user->esAdministrador() && ! $user->esSuperadmin() && $user->nit_empresa
            // (menos cuando está cerrando sesión)
            && ! $request->routeIs('logout')
            // ...y su óptica NO tiene licencia vigente:
            && ! Licencias::puedeIngresar($user->empresa)) {
            // le cierra la sesión (aunque ya estuviera dentro).
            return LoginController::cerrarPorLicenciaInactiva($request, $user);
        }

        // 3) Contraseña temporal sin cambiar: solo puede ver la pantalla de cambio.
        // Contraseña temporal sin cambiar (administradores y empleados): solo pueden ver la pantalla de cambio.
        // Si debe cambiar la contraseña y no está en esa pantalla (ni cerrando sesión)...
        if ($user && $user->debe_cambiar_password
            && ! $request->routeIs('password.cambiar', 'password.cambiar.guardar', 'logout')) {
            // ...lo redirige a /cambiar-password.
            return redirect()->route('password.cambiar');
        }

        // Todo en orden: continúa.
        return $next($request);
    }
}
