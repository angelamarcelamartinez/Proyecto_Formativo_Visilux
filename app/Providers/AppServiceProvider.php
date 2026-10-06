<?php

namespace App\Providers;

use App\Models\Carrito;
use App\Models\DetalleCarritoProducto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El header aparece en TODAS las páginas del sitio, así que el
        // conteo del carrito se calcula aquí una sola vez, en vez de
        // que cada controlador tenga que acordarse de pasarlo.
        View::composer('includes.header', function ($view) {
            $cantidad = 0;

            if (Auth::check()) {
                $carrito = Carrito::where('id_usuario', Auth::id())
                    ->where('id_estado', 1)
                    ->first();

                if ($carrito) {
                    $cantidad = DetalleCarritoProducto::where('id_carrito', $carrito->id_carrito)->sum('cantidad');
                }
            } else {
                // Invitado: el carrito vive en la sesión, como [id_producto => cantidad].
                $cantidad = array_sum(session('carrito_invitado', []));
            }

            $view->with('cartCount', $cantidad);
        });
    }
}
