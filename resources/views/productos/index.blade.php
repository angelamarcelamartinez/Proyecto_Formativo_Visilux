@include('includes.header')

<!-- ======================================================
     BANNER DE LA PÁGINA
====================================================== -->
<section class="page-header">
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Catálogo</span>
        <h1 class="page-title display-5 mb-3">Nuestros Productos</h1>
        <p class="page-subtitle mx-auto">
            Monturas, lentes y accesorios seleccionados para cuidar tu salud visual con estilo.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">

        @if (session('ok'))
            <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i> {{ session('ok') }}
                <a href="{{ route('carrito.index') }}" class="ms-auto btn btn-sm btn-gradient rounded-pill">Ver carrito</a>
            </div>
        @endif
        @if (session('info'))
            <div class="alert alert-warning border-0 shadow-sm rounded-4">
                <i class="bi bi-info-circle-fill"></i> {{ session('info') }}
            </div>
        @endif

        <div class="row g-4">

            <!-- ======================================================
                 BARRA DE FILTROS
            ====================================================== -->
            <div class="col-lg-3">
                <form method="GET" action="{{ route('productos.index') }}" class="filtro-card p-4 shadow-sm">
                    <h6 class="fw-semibold mb-3 text-heading"><i class="bi bi-sliders me-1"></i> Filtrar</h6>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Buscar</label>
                        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" class="form-control" placeholder="Nombre del producto...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Marca</label>
                        <select name="marca" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->id_marca }}" @selected(($filtros['marca'] ?? '') == $marca->id_marca)>
                                    {{ $marca->nom_marca }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id_categoria }}" @selected(($filtros['categoria'] ?? '') == $categoria->id_categoria)>
                                    {{ $categoria->nombre_categoria }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tipo de producto</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo->id_tipo_pro }}" @selected(($filtros['tipo'] ?? '') == $tipo->id_tipo_pro)>
                                    {{ $tipo->nomb_tipo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Precio</label>
                        <div class="d-flex gap-2">
                            <input type="number" name="precio_min" value="{{ $filtros['precio_min'] ?? '' }}" class="form-control" placeholder="Mín">
                            <input type="number" name="precio_max" value="{{ $filtros['precio_max'] ?? '' }}" class="form-control" placeholder="Máx">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Ordenar por</label>
                        <select name="orden" class="form-select">
                            <option value="">Más recientes</option>
                            <option value="precio_asc" @selected(($filtros['orden'] ?? '') == 'precio_asc')>Precio: menor a mayor</option>
                            <option value="precio_desc" @selected(($filtros['orden'] ?? '') == 'precio_desc')>Precio: mayor a menor</option>
                            <option value="nombre" @selected(($filtros['orden'] ?? '') == 'nombre')>Nombre (A-Z)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-gradient rounded-pill w-100 mb-2">
                        <i class="bi bi-funnel-fill me-1"></i> Aplicar filtros
                    </button>
                    <a href="{{ route('productos.index') }}" class="btn btn-link rounded-pill w-100 text-muted text-decoration-none">
                        Limpiar filtros
                    </a>
                </form>
            </div>

            <!-- ======================================================
                 CUADRÍCULA DE PRODUCTOS
            ====================================================== -->
            <div class="col-lg-9">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">{{ $productos->total() }} producto(s) encontrado(s)</p>
                </div>

                @if ($productos->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-emoji-frown display-4 text-muted"></i>
                        <p class="text-muted mt-3">No encontramos productos con esos filtros. Intenta con otros criterios.</p>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($productos as $producto)
                            <div class="col-md-4 col-sm-6">
                                <div class="card product-card border-0 shadow-sm h-100">

                                    <div class="product-img-wrap">
                                        @if ($producto->imagen)
                                            <img src="{{ asset('assets/img/productos/' . $producto->imagen) }}" alt="{{ $producto->nombre_producto }}" class="product-img">
                                        @else
                                            <div class="product-img-placeholder">
                                                <i class="bi bi-eyeglasses"></i>
                                            </div>
                                        @endif
                                        @if ($producto->categoriaProducto)
                                            <span class="product-badge">{{ $producto->categoriaProducto->nombre_categoria }}</span>
                                        @endif
                                    </div>

                                    <div class="card-body d-flex flex-column p-4">
                                        @if ($producto->marca)
                                            <span class="text-uppercase small text-teal fw-semibold mb-1">{{ $producto->marca->nom_marca }}</span>
                                        @endif

                                        <h5 class="fw-semibold mb-2 text-heading">{{ $producto->nombre_producto }}</h5>

                                        <p class="text-muted small mb-3 flex-grow-1">
                                            {{ \Illuminate\Support\Str::limit($producto->descripcion, 80, '...') }}
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="fw-bold fs-5 text-heading">
                                                @if ($producto->precio)
                                                    ${{ number_format($producto->precio, 0, ',', '.') }}
                                                @else
                                                    <span class="small text-muted">Consultar</span>
                                                @endif
                                            </span>

                                            <form method="POST" action="{{ route('carrito.agregar', $producto->id_producto) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-gradient rounded-circle add-cart-btn" title="Agregar al carrito" {{ $producto->precio ? '' : 'disabled' }}>
                                                    <i class="bi bi-cart-plus-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-center mt-5 paginacion-visilux">
                        {{ $productos->links('pagination::bootstrap-5') }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>

@include('includes.footer')
