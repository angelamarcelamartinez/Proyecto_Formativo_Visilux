@include('includes.header')
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="kids-page">

    {{-- Banner --}}
    <section class="kids-hero text-center">
        <div class="container position-relative">
            <span class="kids-float k1">☁️</span>
            <span class="kids-float k2">⭐</span>
            <span class="kids-float k3">🌈</span>
            <span class="kids-float k4">💖</span>
            <span class="kids-sticker">¡Para peques!</span>
            <h1 class="kids-title">Gafas <span>y</span> cositas<br>para <em>niños</em> 👓</h1>
            <p class="kids-sub">Monturas resistentes, livianas y divertidas para que ver bien sea una aventura.</p>

            <form method="GET" action="{{ route('productos.ninos') }}" class="kids-search">
                <i class="bi bi-search"></i>
                <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="¿Qué gafas buscas?">
                <select name="orden" onchange="this.form.submit()">
                    <option value="">Más nuevas</option>
                    <option value="precio_asc" @selected(($filtros['orden'] ?? '') == 'precio_asc')>Más baratas</option>
                    <option value="precio_desc" @selected(($filtros['orden'] ?? '') == 'precio_desc')>Más caras</option>
                </select>
                <button type="submit">¡Buscar!</button>
            </form>
        </div>
    </section>

    <section class="kids-body">
        <div class="container">

            @if (session('ok'))
                <div class="kids-alert">🎉 {{ session('ok') }}
                    <a href="{{ route('carrito.index') }}">Ver carrito</a></div>
            @endif
            @if (session('info'))
                <div class="kids-alert kids-alert-warn">{{ session('info') }}</div>
            @endif

            @if ($productos->isEmpty())
                <div class="kids-empty">
                    <div>🧸</div>
                    <h3>¡Pronto llegarán nuevas gafas!</h3>
                    <p>Todavía no hay productos infantiles con esa búsqueda.</p>
                    <a href="{{ route('productos.ninos') }}" class="kids-btn">Ver todo</a>
                </div>
            @else
                @php $colores = ['kc-rojo','kc-azul','kc-amarillo','kc-verde','kc-rosa','kc-morado']; @endphp
                <div class="row g-4">
                    @foreach ($productos as $i => $producto)
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="kids-card {{ $colores[$i % 6] }}">
                                <div class="kids-img">
                                    @if ($producto->imagen)
                                        <img src="{{ asset('assets/img/productos/' . $producto->imagen) }}" alt="{{ $producto->nombre_producto }}">
                                    @else
                                        <i class="bi bi-eyeglasses"></i>
                                    @endif
                                    <span class="kids-tag">{{ ['⭐','🚀','🦄','🐥','🌈','🎈'][$i % 6] }}</span>
                                </div>
                                <div class="kids-info">
                                    @if ($producto->marca)
                                        <small>{{ $producto->marca->nom_marca }}</small>
                                    @endif
                                    <h5>{{ $producto->nombre_producto }}</h5>
                                    <p>{{ \Illuminate\Support\Str::limit($producto->descripcion, 60, '...') }}</p>
                                    <div class="kids-foot">
                                        <span class="kids-price">
                                            @if ($producto->precio)${{ number_format($producto->precio, 0, ',', '.') }}@else<small>Consultar</small>@endif
                                        </span>
                                        <form method="POST" action="{{ route('carrito.agregar', $producto->id_producto) }}">
                                            @csrf
                                            <button class="kids-add" title="¡Lo quiero!" {{ $producto->precio ? '' : 'disabled' }}>
                                                <i class="bi bi-cart-plus-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-center mt-5">
                    {{ $productos->links('pagination::bootstrap-5') }}
                </div>
            @endif

            <div class="kids-tip">
                <span>👀</span>
                <div><strong>¿Sabías que…?</strong> Un examen visual a tiempo ayuda a que los niños aprendan y jueguen mejor.
                    <a href="{{ route('citas.create') }}">¡Agenda su cita!</a></div>
            </div>
        </div>
    </section>
</div>

@include('includes.footer')
