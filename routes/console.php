<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Licencias de VisiOptica
|--------------------------------------------------------------------------
| php artisan licencias:revisar
|   - pasa a "vencida" las licencias cuya fecha_fin ya pasó;
|   - envía el correo de "tu licencia está por vencer" (a los 30 y a los 7 días).
|
| Se programa todos los días a las 8:00. Para que corra solo, el servidor debe
| ejecutar cada minuto:  php artisan schedule:run
| (en Hostinger: un "Cron Job"; en Windows con XAMPP: el Programador de tareas).
*/
Artisan::command('licencias:revisar', function () {
    $resultado = \App\Support\AvisosLicencia::enviarPendientes();

    foreach ($resultado['detalle'] as $linea) {
        $this->line($linea);
    }

    $this->info("Avisos enviados: {$resultado['enviados']}. Errores: {$resultado['errores']}.");
})->purpose('Marca licencias vencidas y envía los avisos de vencimiento por correo');

\Illuminate\Support\Facades\Schedule::command('licencias:revisar')->dailyAt('08:00');
