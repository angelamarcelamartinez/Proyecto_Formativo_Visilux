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

        // Orden: más recientes primero, o por precio si el usuario lo pide
        switch ($request->get('orden')) {
            case 'precio_asc':
                $query->orderBy('precio', 'asc');
                break;
            case 'precio_desc':
                $query->orderBy('precio', 'desc');
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
            'filtros' => $request->only(['buscar', 'marca', 'categoria', 'tipo', 'precio_min', 'precio_max', 'orden']),
            'pageTitle' => 'Productos',
        ]);
    }
}
