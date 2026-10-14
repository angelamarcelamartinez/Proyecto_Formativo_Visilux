<?php

namespace App\Providers;

use App\Models\Carrito;
use App\Models\DetalleCarritoProducto;
use App\Models\Empresa;
use App\Models\PaginaEmpresa;
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

        // Logo, contacto y redes del header y footer salen de la página de la
        // óptica que se está viendo (o de la principal si no hay ninguna).
        View::composer(['includes.header', 'includes.footer'], function ($view) {
            static $cache = null;

            if ($cache === null) {
                $pagina = $view->getData()['pagina'] ?? null;

                if (! $pagina instanceof PaginaEmpresa) {
                    $empresa = Empresa::find(session('optica_nit', config('visioptica.empresa_principal')))
                        ?? Empresa::principal();
                    $pagina = $empresa ? PaginaEmpresa::deEmpresa($empresa) : null;
                }

                $cache = [
                    'sitio' => $pagina,
                    'sitioEmpresa' => $pagina?->empresa,
                ];
            }

            $view->with($cache);
        });

        // Número de solicitudes de plan por aprobar, para el menú del superadmin.
        View::composer('layouts.superadmin', function ($view) {
            $view->with('solicitudesPendientes', \App\Models\Licencia::where('estado', 'pendiente')->count());

            // Administradores bloqueados (inactivos): solo el superadmin puede desbloquearlos.
            $bloqueados = \App\Models\Usuario::with('empresa:nit,nombre')
                ->where('id_rol', config('visioptica.rol_admin'))
                ->where('id_estado', 2)
                ->get();

            $motivos = \App\Models\Actividad::where('tabla', 'usuario')
                ->where('descripcion', 'like', 'Cuenta inactivada por 4 intentos%')
                ->whereIn('id_registro', $bloqueados->pluck('documento'))
                ->orderBy('fecha')
                ->get()
                ->keyBy('id_registro');

            $bloqueados->each(function ($u) use ($motivos) {
                $u->bloqueado_desde = $motivos[$u->documento]->fecha ?? null;
                $u->por_intentos = isset($motivos[$u->documento]);
            });

            $view->with('adminsBloqueados', $bloqueados);
        });

        // Notificación del candado en el panel de la óptica: usuarios (no administradores)
        // de esa óptica que están bloqueados. El administrador los desbloquea desde ahí.
        View::composer('layouts.admin', function ($view) {
            $yo = Auth::user();
            $bloqueados = collect();

            if ($yo && $yo->nit_empresa) {
                $bloqueados = \App\Models\Usuario::where('nit_empresa', $yo->nit_empresa)
                    ->whereNotIn('id_rol', [config('visioptica.rol_admin'), 6])
                    ->where('id_estado', 2)
                    ->get();

                $motivos = \App\Models\Actividad::where('tabla', 'usuario')
                    ->where('descripcion', 'like', 'Cuenta inactivada por 4 intentos%')
                    ->whereIn('id_registro', $bloqueados->pluck('documento'))
                    ->orderBy('fecha')
                    ->get()
                    ->keyBy('id_registro');

                $bloqueados->each(function ($u) use ($motivos) {
                    $u->bloqueado_desde = $motivos[$u->documento]->fecha ?? null;
                    $u->por_intentos = isset($motivos[$u->documento]);
                });
            }

            $view->with('usuariosBloqueados', $bloqueados);
        });

        // Aviso de pago en el panel de la óptica cuando faltan 30 días o menos.
        View::composer(['layouts.admin', 'admin.*'], function ($view) {
            $empresa = Auth::user()?->empresa;
            $dias = $empresa?->diasRestantes();

            $view->with([
                'miEmpresa' => $empresa,
                'avisoLicenciaDias' => ($dias !== null && $dias <= config('visioptica.dias_aviso')) ? $dias : null,
            ]);
        });
    }
}
