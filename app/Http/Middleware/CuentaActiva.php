<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\LoginController;
use App\Support\BloqueoUsuarios;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si un administrador inactiva a un usuario que tiene la sesión abierta
 * (o alguien inactivo entra al restablecer su contraseña), se le cierra
 * la sesión en la siguiente página que abra.
 */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->esSuperadmin() && BloqueoUsuarios::estaInactivo($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => LoginController::MENSAJE_INACTIVO]);
        }

        return $next($request);
    }
}
