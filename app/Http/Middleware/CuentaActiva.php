<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\LoginController;
// BloqueoUsuarios: sabe si una cuenta está inactiva.
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
// Middleware GLOBAL: si inactivaron una cuenta mientras tenía la sesión abierta, la saca.
class CuentaActiva
{
    // Se ejecuta en cada petición.
    public function handle(Request $request, Closure $next): Response
    {
        // Usuario logueado.
        $user = Auth::user();

        // Si hay sesión, no es superadmin y su cuenta está inactiva (id_estado 2)...
        if ($user && ! $user->esSuperadmin() && BloqueoUsuarios::estaInactivo($user)) {
            // ...cierra la sesión,
            Auth::logout();
            // destruye la sesión,
            $request->session()->invalidate();
            // renueva el token CSRF
            $request->session()->regenerateToken();

            // y lo manda al login con el mensaje de cuenta inactiva.
            return redirect()->route('login')
                ->withErrors(['email' => LoginController::MENSAJE_INACTIVO]);
        }

        // Cuenta activa: continúa.
        return $next($request);
    }
}
