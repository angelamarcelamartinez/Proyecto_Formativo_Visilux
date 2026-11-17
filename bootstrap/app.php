<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Archivo de arranque de Laravel 12: aquí se registran rutas y middlewares.
return Application::configure(basePath: dirname(__DIR__))
    // Archivos de rutas: web (routes/web.php), comandos de consola y /up (comprobación de salud).
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Registro de middlewares.
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias: nombres cortos que se usan en routes/web.php, por ejemplo ->middleware(['superadmin']).
        $middleware->alias([
            // admin → solo administradores (rol 1).
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            // superadmin → solo el superadmin (si no, responde 404).
            'superadmin' => \App\Http\Middleware\SoloSuperadmin::class,
            // rol → solo deja pasar a los roles indicados, por ejemplo ->middleware('rol:4') o 'rol:7'.
            'rol' => \App\Http\Middleware\SoloRol::class,
            // cambiar.password → obliga a cambiar la contraseña temporal.
            'cambiar.password' => \App\Http\Middleware\CambiarPasswordObligatorio::class,
            // licencia → exige licencia vigente o manda a renovar.
            'licencia' => \App\Http\Middleware\VerificarLicencia::class,
        ]);

        // Estados de licencia al día y sesión cerrada a quien no es administrador si su licencia está inactiva
        // LicenciasAlDia se ejecuta en TODAS las peticiones web: actualiza vencimientos y saca a quien no puede entrar.
        $middleware->appendToGroup('web', \App\Http\Middleware\LicenciasAlDia::class);
        // Saca a los usuarios inactivados aunque ya tuvieran la sesión abierta.
        // CuentaActiva también corre siempre: cierra la sesión de quien haya sido inactivado.
        $middleware->web(append: [
            \App\Http\Middleware\CuentaActiva::class,
        ]);
    })
    // Manejo de errores.
    ->withExceptions(function (Exceptions $exceptions): void {
        // Si el formulario de contratar vuelve con errores, los datos de la
        // tarjeta no se guardan en la sesión (aunque el pago sea simulado).
        // Estos campos NUNCA se devuelven al formulario cuando hay un error (contraseñas y datos de tarjeta).
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'tarjeta_numero', 'tarjeta_cvv']);
    })->create();
