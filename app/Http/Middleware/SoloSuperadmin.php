<?php

// Middleware = portero: se ejecuta ANTES del controlador y decide si deja pasar la petición.
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege el panel /superadmin: solo entra el rol Superadmin (id_rol = 6).
 *
 * Para cualquier otra persona (sin sesión, paciente o administrador de óptica)
 * la dirección responde 404, como si no existiera, para no revelar que hay un
 * panel de superadmin. El login del superadmin está en una URL secreta
 * (config/visioptica.php -> superadmin_login_path).
 */
// Alias "superadmin" (bootstrap/app.php). Protege TODAS las rutas /superadmin del archivo routes/web.php.
class SoloSuperadmin
{
    // handle() recibe la petición ($request) y $next, que es "continuar al siguiente paso" (el controlador).
    public function handle(Request $request, Closure $next): Response
    {
        // Usuario con sesión iniciada (null si nadie inició sesión).
        $user = Auth::user();

        // Si no hay sesión, o el usuario no es superadmin (id_rol 6)...
        if (! $user || ! $user->esSuperadmin()) {
            // ...responde 404 "no existe", en vez de 403 o login, para no revelar que el panel existe.
            abort(404);
        }

        // Es superadmin: deja pasar al controlador.
        return $next($request);
    }
}
