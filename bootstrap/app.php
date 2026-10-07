<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'superadmin' => \App\Http\Middleware\SoloSuperadmin::class,
            'cambiar.password' => \App\Http\Middleware\CambiarPasswordObligatorio::class,
            'licencia' => \App\Http\Middleware\VerificarLicencia::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Si el formulario de contratar vuelve con errores, los datos de la
        // tarjeta no se guardan en la sesión (aunque el pago sea simulado).
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'tarjeta_numero', 'tarjeta_cvv']);
    })->create();
