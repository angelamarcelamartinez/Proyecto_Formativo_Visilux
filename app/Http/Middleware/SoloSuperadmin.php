<?php

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
class SoloSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->esSuperadmin()) {
            abort(404);
        }

        return $next($request);
    }
}
