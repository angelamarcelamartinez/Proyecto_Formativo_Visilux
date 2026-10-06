@include('includes.header')

<section class="page-header">
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Tu compra</span>
        <h1 class="page-title display-5 mb-3">Mi Carrito</h1>
    </div>
</section>

<section class="py-5">
    <div class="container">

        @if (session('ok'))
            <div class="alert alert-success border-0 shadow-sm rounded-4">
                <i class="bi bi-check-circle-fill"></i> {{ session('ok') }}
            </div>
        @endif

        @guest
            <div class="alert alert-light border shadow-sm rounded-4 small">
                <i class="bi bi-info-circle"></i> Estás comprando como invitado. Tus productos se guardan en este navegador
                y no se pierden si decides iniciar sesión más adelante.
            </div>
        @endguest

        @if ($detalles->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted"></i>
                <p class="text-muted mt-3 mb-4">Tu carrito está vacío por ahora.</p>
                <a href="{{ route('productos.index') }}" class="btn btn-gradient rounded-pill px-4">
                    <i class="bi bi-eyeglasses me-1"></i> Ver productos
                </a>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="d-flex flex-column gap-3">
                        @foreach ($detalles as $detalle)
                            <div class="carrito-item p-3 d-flex align-items-center gap-3">

                                @if ($detalle['producto'] && $detalle['producto']->imagen)
                                    <img src="{{ asset('assets/img/productos/' . $detalle['producto']->imagen) }}" class="carrito-item-img" alt="{{ $detalle['producto']->nombre_producto }}">
                                @else
                                    <div class="carrito-img-placeholder">
                                        <i class="bi bi-eyeglasses"></i>
                                    </div>
                                @endif

                                <div class="flex-grow-1">
                                    <h6 class="fw-semibold mb-1 text-heading">
                                        {{ $detalle['producto']->nombre_producto ?? 'Producto no disponible' }}
                                    </h6>
                                    <p class="text-muted small mb-0">
                                        ${{ number_format($detalle['precio_unitario'], 0, ',', '.') }} c/u
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('carrito.actualizar', $detalle['id']) }}" class="d-flex align-items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="cantidad" value="{{ $detalle['cantidad'] }}" min="1" class="form-control form-control-sm text-center" style="width: 60px;">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill">Actualizar</button>
                                </form>

                                <span class="fw-bold text-heading" style="min-width: 90px; text-align: right;">
                                    ${{ number_format($detalle['subtotal'], 0, ',', '.') }}
                                </span>

                                <form method="POST" action="{{ route('carrito.eliminar', $detalle['id']) }}" onsubmit="return confirm('¿Quitar este producto del carrito?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Quitar">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="filtro-card p-4">
                        <h5 class="fw-semibold mb-4 text-heading">Resumen</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span>${{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-semibold">Total</span>
                            <span class="fw-bold fs-4 text-heading">${{ number_format($total, 0, ',', '.') }}</span>
                        </div>

                        <form method="POST" action="{{ route('carrito.finalizar') }}">
                            @csrf
                            <button type="submit" class="btn btn-gradient rounded-pill w-100 mb-2">
                                @guest
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión para continuar
                                @else
                                    <i class="bi bi-credit-card me-1"></i> Finalizar compra
                                @endguest
                            </button>
                        </form>

                        <a href="{{ route('productos.index') }}" class="btn btn-link rounded-pill w-100 text-muted text-decoration-none">
                            Seguir comprando
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>
</section>

@include('includes.footer')
