<?php

namespace App\Http\Middleware;

use App\Support\Licencias;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antes de dejar entrar al panel /admin revisa que la óptica del usuario:
 *  - exista,
 *  - no esté suspendida por el superadmin,
 *  - tenga una licencia activa que cubra el día de hoy.
 * Si algo falla, cierra la sesión y lo devuelve al login con el motivo.
 * (El login ya revisa esto al entrar; aquí se cubre al que tenía la sesión abierta
 * justo cuando venció la licencia.)
 */
class VerificarLicencia
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! Licencias::puedeIngresar($user?->empresa)) {
            return \App\Http\Controllers\Auth\LoginController::cerrarPorLicencia($request, $user);
        }

        return $next($request);
    }
}
