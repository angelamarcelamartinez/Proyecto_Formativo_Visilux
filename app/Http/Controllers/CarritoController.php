<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\DetalleCarritoProducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarritoController extends Controller
{
    /** Clave de sesión donde vive el carrito de un invitado (sin sesión iniciada). */
    private const CLAVE_SESION = 'carrito_invitado';

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

    /**
     * Agrega (o suma 1 a) un producto al carrito.
     *
     * Ya NO exige sesión iniciada: si la persona es invitada, el
     * producto se guarda en la sesión del navegador. Si ya inició
     * sesión, se guarda directo en la base de datos, como antes.
     */
    public function agregar(Request $request, int $id_producto)
    {
        $producto = Producto::activos()->findOrFail($id_producto);

        if (Auth::check()) {
            $carrito = $this->carritoActivoDe(Auth::id());

            $detalle = DetalleCarritoProducto::where('id_carrito', $carrito->id_carrito)
                ->where('id_producto', $producto->id_producto)
                ->first();

            if ($detalle) {
                $detalle->cantidad += 1;
                $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;
                $detalle->save();
            } else {
                DetalleCarritoProducto::create([
                    'id_carrito' => $carrito->id_carrito,
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 1,
                    'precio_unitario' => $producto->precio ?? 0,
                    'subtotal' => $producto->precio ?? 0,
                ]);
            }
        } else {
            // Invitado: el carrito es solo un arreglo [id_producto => cantidad] en la sesión.
            $carritoSesion = session(self::CLAVE_SESION, []);
            $carritoSesion[$id_producto] = ($carritoSesion[$id_producto] ?? 0) + 1;
            session([self::CLAVE_SESION => $carritoSesion]);
        }

        return back()->with('ok', "«{$producto->nombre_producto}» se agregó al carrito.");
    }

    /**
     * Arma la lista de líneas del carrito para mostrarla en la vista,
     * sin importar si la persona es invitada o tiene sesión iniciada
     * -- en los 2 casos entrega objetos con la misma forma, para que
     * la vista no tenga que preocuparse por la diferencia.
     */
    private function obtenerLineas(): array
    {
        if (Auth::check()) {
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

        // Invitado: reconstruimos las líneas a partir de la sesión + los productos reales.
        $carritoSesion = session(self::CLAVE_SESION, []);
        if (empty($carritoSesion)) {
            return [collect(), 0];
        }

        $productos = Producto::whereIn('id_producto', array_keys($carritoSesion))->get()->keyBy('id_producto');

        $lineas = collect($carritoSesion)->map(function ($cantidad, $id_producto) use ($productos) {
            $producto = $productos->get($id_producto);
            if (! $producto) {
                return null; // el producto pudo haberse desactivado despues de agregarlo
            }
            $precio = $producto->precio ?? 0;
            return [
                'id' => $id_producto, // para invitados, el "id" de la linea es el id_producto
                'producto' => $producto,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $precio * $cantidad,
            ];
        })->filter()->values();

        return [$lineas, $lineas->sum('subtotal')];
    }

    public function index()
    {
        [$lineas, $total] = $this->obtenerLineas();

        return view('carrito.index', [
            'detalles' => $lineas,
            'total' => $total,
            'pageTitle' => 'Mi carrito',
        ]);
    }

    /**
     * Cambia la cantidad de una línea. $id es el id_detalle_carr_pro
     * si hay sesión iniciada, o el id_producto si es invitado.
     */
    public function actualizar(Request $request, $id)
    {
        $cantidad = max(1, (int) $request->get('cantidad', 1));

        if (Auth::check()) {
            $detalle = DetalleCarritoProducto::with('carrito')->findOrFail($id);

            if ($detalle->carrito->id_usuario != Auth::id()) {
                abort(403);
            }

            $detalle->cantidad = $cantidad;
            $detalle->subtotal = $cantidad * $detalle->precio_unitario;
            $detalle->save();
        } else {
            $carritoSesion = session(self::CLAVE_SESION, []);
            if (isset($carritoSesion[$id])) {
                $carritoSesion[$id] = $cantidad;
                session([self::CLAVE_SESION => $carritoSesion]);
            }
        }

        return back()->with('ok', 'Cantidad actualizada.');
    }

    /**
     * Quita una línea completa del carrito (invitado o con sesión).
     */
    public function eliminar($id)
    {
        if (Auth::check()) {
            $detalle = DetalleCarritoProducto::with('carrito')->findOrFail($id);

            if ($detalle->carrito->id_usuario != Auth::id()) {
                abort(403);
            }

            $detalle->delete();
        } else {
            $carritoSesion = session(self::CLAVE_SESION, []);
            unset($carritoSesion[$id]);
            session([self::CLAVE_SESION => $carritoSesion]);
        }

        return back()->with('ok', 'Producto eliminado del carrito.');
    }

    /**
     * Botón "Finalizar compra". Si es invitado, lo manda a iniciar
     * sesión SIN perder lo que ya agregó (el carrito de sesión se
     * fusiona solo al iniciar sesión, ver fusionarCarritoInvitado()).
     */
    public function finalizar(Request $request)
    {
        if (! Auth::check()) {
            $request->session()->put('url.intended', route('carrito.index'));

            return redirect()->route('login')
                ->with('info', 'Inicia sesión para finalizar tu compra. Tus productos siguen en el carrito.');
        }

        // Aquí, más adelante, iría la lógica real de checkout/pago.
        return back()->with('ok', 'Compra en proceso (próximamente).');
    }

    /**
     * Se llama automáticamente justo después de un login exitoso
     * (desde LoginController::authenticated()). Toma lo que la
     * persona agregó al carrito como invitada y lo traslada a su
     * carrito real en la base de datos, sumando cantidades si ya
     * tenía algo de antes.
     */
    public function fusionarCarritoInvitado($documento): void
    {
        $carritoSesion = session(self::CLAVE_SESION, []);
        if (empty($carritoSesion)) {
            return;
        }

        $carrito = $this->carritoActivoDe($documento);

        foreach ($carritoSesion as $id_producto => $cantidad) {
            $producto = Producto::find($id_producto);
            if (! $producto) {
                continue;
            }

            $detalle = DetalleCarritoProducto::where('id_carrito', $carrito->id_carrito)
                ->where('id_producto', $id_producto)
                ->first();

            if ($detalle) {
                $detalle->cantidad += $cantidad;
                $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;
                $detalle->save();
            } else {
                $precio = $producto->precio ?? 0;
                DetalleCarritoProducto::create([
                    'id_carrito' => $carrito->id_carrito,
                    'id_producto' => $id_producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $precio * $cantidad,
                ]);
            }
        }

        session()->forget(self::CLAVE_SESION);
    }
}
