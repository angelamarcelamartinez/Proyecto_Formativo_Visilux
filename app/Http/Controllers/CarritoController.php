<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\DetalleCarritoProducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarritoController extends Controller
{
    /** Clave de sesión: producto que la persona quiso agregar justo antes de iniciar sesión o registrarse. */
    private const CLAVE_PENDIENTE = 'carrito_pendiente';

    /**
     * Busca el carrito "abierto" del usuario (id_estado = 1), o le
     * crea uno nuevo si todavía no tiene ninguno activo.
     */
    private function carritoActivoDe($documento): Carrito
    {
        $carrito = Carrito::where('id_usuario', $documento)
            ->where('id_estado', 1)
            ->first();

        if (! $carrito) {
            $carrito = Carrito::create([
                'fecha_actual' => now()->toDateString(),
                'id_usuario' => $documento,
                'id_estado' => 1,
            ]);
        }

        return $carrito;
    }

    /** Suma $cantidad del producto al carrito abierto del usuario (crea la línea si no existe). */
    private function sumarAlCarrito($documento, Producto $producto, int $cantidad = 1): void
    {
        $carrito = $this->carritoActivoDe($documento);
        $precio = $producto->precio ?? 0;

        $detalle = DetalleCarritoProducto::where('id_carrito', $carrito->id_carrito)
            ->where('id_producto', $producto->id_producto)
            ->first();

        if ($detalle) {
            $detalle->cantidad += $cantidad;
            $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;
            $detalle->save();

            return;
        }

        DetalleCarritoProducto::create([
            'id_carrito' => $carrito->id_carrito,
            'id_producto' => $producto->id_producto,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $precio * $cantidad,
        ]);
    }

    /**
     * El optómetra tiene su cuenta en la tabla `optometra`, no en `usuario`, y carrito.id_usuario apunta a `usuario`:
     * no puede comprar. Si lo intenta, vuelve al inicio con un aviso (en vez de un error de la base de datos).
     */
    private function sinCarritoParaPersonal()
    {
        if (Auth::user() instanceof \App\Models\Optometra) {
            return redirect()->route('home')->with('info', 'Tu cuenta de optómetra no tiene carrito de compras.');
        }

        return null;
    }

    /** Manda a iniciar sesión (o registrarse) y, al volver, deja a la persona en el carrito. */
    private function pedirSesion(Request $request, string $mensaje)
    {
        $request->session()->put('url.intended', route('carrito.index'));

        return redirect()->route('login')->with('info', $mensaje);
    }

    /**
     * Agrega (o suma 1 a) un producto al carrito. Comprar exige tener cuenta: si no hay sesión,
     * se recuerda el producto, se manda a iniciar sesión o registrarse y se agrega al volver.
     */
    public function agregar(Request $request, int $id_producto)
    {
        $producto = Producto::activos()->findOrFail($id_producto);

        if (! Auth::check()) {
            $pendiente = $request->session()->get(self::CLAVE_PENDIENTE, []);
            $pendiente[$id_producto] = ($pendiente[$id_producto] ?? 0) + 1;
            $request->session()->put(self::CLAVE_PENDIENTE, $pendiente);

            return $this->pedirSesion($request, 'Inicia sesión o regístrate para comprar. Tu producto te espera en el carrito.');
        }

        if ($bloqueo = $this->sinCarritoParaPersonal()) {
            return $bloqueo;
        }

        $this->sumarAlCarrito(Auth::id(), $producto);

        return back()->with('ok', "«{$producto->nombre_producto}» se agregó al carrito.");
    }

    /** Líneas del carrito del usuario con sesión y el total. */
    private function obtenerLineas(): array
    {
        $carrito = $this->carritoActivoDe(Auth::id());

        $lineas = DetalleCarritoProducto::with('producto')
            ->where('id_carrito', $carrito->id_carrito)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id_detalle_carr_pro,
                'producto' => $d->producto,
                'cantidad' => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
                'subtotal' => $d->subtotal,
            ]);

        return [$lineas, $lineas->sum('subtotal')];
    }

    public function index(Request $request)
    {
        if (! Auth::check()) {
            return $this->pedirSesion($request, 'Inicia sesión o regístrate para ver tu carrito.');
        }

        if ($bloqueo = $this->sinCarritoParaPersonal()) {
            return $bloqueo;
        }

        [$lineas, $total] = $this->obtenerLineas();

        return view('carrito.index', [
            'detalles' => $lineas,
            'total' => $total,
            'pageTitle' => 'Mi carrito',
        ]);
    }

    /** Cambia la cantidad de una línea ($id es el id_detalle_carr_pro). */
    public function actualizar(Request $request, $id)
    {
        if (! Auth::check()) {
            return $this->pedirSesion($request, 'Inicia sesión para modificar tu carrito.');
        }

        if ($bloqueo = $this->sinCarritoParaPersonal()) {
            return $bloqueo;
        }

        $cantidad = max(1, (int) $request->get('cantidad', 1));

        $detalle = DetalleCarritoProducto::with('carrito')->findOrFail($id);

        if ($detalle->carrito->id_usuario != Auth::id()) {
            abort(403);
        }

        $detalle->cantidad = $cantidad;
        $detalle->subtotal = $cantidad * $detalle->precio_unitario;
        $detalle->save();

        return back()->with('ok', 'Cantidad actualizada.');
    }

    /** Quita una línea completa del carrito. */
    public function eliminar(Request $request, $id)
    {
        if (! Auth::check()) {
            return $this->pedirSesion($request, 'Inicia sesión para modificar tu carrito.');
        }

        if ($bloqueo = $this->sinCarritoParaPersonal()) {
            return $bloqueo;
        }

        $detalle = DetalleCarritoProducto::with('carrito')->findOrFail($id);

        if ($detalle->carrito->id_usuario != Auth::id()) {
            abort(403);
        }

        $detalle->delete();

        return back()->with('ok', 'Producto eliminado del carrito.');
    }

    /** Botón "Finalizar compra". */
    public function finalizar(Request $request)
    {
        if (! Auth::check()) {
            return $this->pedirSesion($request, 'Inicia sesión o regístrate para finalizar tu compra.');
        }

        if ($bloqueo = $this->sinCarritoParaPersonal()) {
            return $bloqueo;
        }

        // Aquí, más adelante, iría la lógica real de checkout/pago.
        return back()->with('ok', 'Compra en proceso (próximamente).');
    }

    /**
     * Se llama justo después de iniciar sesión o registrarse (LoginController y RegisterController):
     * agrega al carrito real el producto que la persona quiso comprar antes de entrar.
     */
    public function agregarPendiente($documento): void
    {
        $pendiente = session(self::CLAVE_PENDIENTE, []);
        if (empty($pendiente)) {
            return;
        }

        // Un optómetra no puede tener carrito: se descarta lo que había quedado pendiente.
        if (Auth::user() instanceof \App\Models\Optometra) {
            session()->forget(self::CLAVE_PENDIENTE);

            return;
        }

        foreach ($pendiente as $id_producto => $cantidad) {
            $producto = Producto::activos()->find($id_producto);
            if ($producto) {
                $this->sumarAlCarrito($documento, $producto, (int) $cantidad);
            }
        }

        session()->forget(self::CLAVE_PENDIENTE);
    }
}
