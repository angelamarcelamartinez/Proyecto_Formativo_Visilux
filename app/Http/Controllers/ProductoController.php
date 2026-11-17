<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Marca;
use App\Models\CategoriaProducto;
use App\Models\TipoProducto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Catálogo público de productos, con filtros por marca, categoría,
     * tipo de producto y rango de precio, más paginación.
     */
    public function index(Request $request)
    {
        $query = Producto::activos()->with(['marca', 'categoriaProducto', 'tipoProducto']);

        // Búsqueda por nombre (la caja de texto de arriba del catálogo)
        if ($buscar = trim((string) $request->get('buscar', ''))) {
            $query->where('nombre_producto', 'like', "%{$buscar}%");
        }

        // Filtros de la barra lateral -- cada uno es opcional
        if ($request->filled('marca')) {
            $query->where('id_marca', $request->get('marca'));
        }
        if ($request->filled('categoria')) {
            $query->where('id_categoria', $request->get('categoria'));
        }
        if ($request->filled('tipo')) {
            $query->where('id_tipo_pro', $request->get('tipo'));
        }
        if ($request->filled('precio_min')) {
            $query->where('precio', '>=', $request->get('precio_min'));
        }
        if ($request->filled('precio_max')) {
            $query->where('precio', '<=', $request->get('precio_max'));
        }
        // Gafas con protección UV (viene del botón de la sección de rayos UV del inicio: /productos?uv=1).
        // No hay una columna "uv" en producto, así que se toman los lentes de sol (categoría 2)
        // y los productos cuyo nombre o descripción hablan de filtro UV, polarizado o fotosensible.
        if ($request->boolean('uv')) {
            $query->where(function ($q) {
                $q->where('id_categoria', 2);
                foreach (['%UV%', '%polariz%', '%fotosens%', '%transitions%'] as $palabra) {
                    $q->orWhere('nombre_producto', 'like', $palabra)->orWhere('descripcion', 'like', $palabra);
                }
            });
        }

        // Orden: más recientes primero, o por precio si el usuario lo pide
        switch ($request->get('orden')) {
            // Los productos sin precio («Consultar») van siempre al final
            case 'precio_asc':
                $query->orderByRaw('precio IS NULL')->orderBy('precio', 'asc');
                break;
            case 'precio_desc':
                $query->orderByRaw('precio IS NULL')->orderBy('precio', 'desc');
                break;
            case 'nombre':
                $query->orderBy('nombre_producto', 'asc');
                break;
            default:
                $query->orderByDesc('id_producto');
        }

        $productos = $query->paginate(9)->withQueryString();

        // Opciones para los <select> de la barra de filtros -- solo las activas.
        $marcas = Marca::where('id_estado', 1)->orderBy('nom_marca')->get();
        $categorias = CategoriaProducto::where('id_estado', 1)->orderBy('nombre_categoria')->get();
        $tipos = TipoProducto::where('id_estado', 1)->orderBy('nomb_tipo')->get();

        return view('productos.index', [
            'productos' => $productos,
            'marcas' => $marcas,
            'categorias' => $categorias,
            'tipos' => $tipos,
            'filtros' => $request->only(['buscar', 'marca', 'categoria', 'tipo', 'precio_min', 'precio_max', 'orden', 'uv']),
            'pageTitle' => 'Productos',
        ]);
    }

    /**
     * Catálogo infantil: solo productos de la categoría "Pediátrica" (id 4).
     */
    public function ninos(Request $request)
    {
        $query = Producto::activos()->with(['marca', 'categoriaProducto'])
            ->where(function ($q) {
                // Categoría Pediátrica, o nombre que indique que es infantil
                $q->where('id_categoria', 4)
                  ->orWhere('nombre_producto', 'like', '%niño%')
                  ->orWhere('nombre_producto', 'like', '%niña%')
                  ->orWhere('nombre_producto', 'like', '%infantil%')
                  ->orWhere('nombre_producto', 'like', '%kids%');
            });

        if ($buscar = trim((string) $request->get('buscar', ''))) {
            $query->where('nombre_producto', 'like', "%{$buscar}%");
        }

        switch ($request->get('orden')) {
            // Los productos sin precio («Consultar») van siempre al final
            case 'precio_asc':  $query->orderByRaw('precio IS NULL')->orderBy('precio', 'asc'); break;
            case 'precio_desc': $query->orderByRaw('precio IS NULL')->orderBy('precio', 'desc'); break;
            default:            $query->orderByDesc('id_producto');
        }

        return view('productos.ninos', [
            'productos' => $query->paginate(8)->withQueryString(),
            'filtros' => $request->only(['buscar', 'orden']),
            'pageTitle' => 'Gafas para niños',
        ]);
    }
}
