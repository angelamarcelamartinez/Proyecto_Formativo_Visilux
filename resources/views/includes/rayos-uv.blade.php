{{-- Sección de rayos UV del inicio (resources/views/index.blade.php).
     Recibe $uv = ApisExternas::uvActual() (o null si la API open-meteo.com no respondió). --}}
<section class="py-5" id="rayos-uv" style="background: #fffaf0;">
    <div class="container">
        <div class="row align-items-center g-4">

            <div class="col-lg-7">
                <span class="ini-eyebrow">Rayos UV</span>
                <h2 class="fw-bold mb-3">Protege tus ojos del sol todos los días</h2>
                <p class="text-muted">
                    La radiación ultravioleta (UV) no se ve ni se siente, pero llega a los ojos incluso en días nublados.
                    Con el tiempo puede causar cataratas, daño en la retina, pterigio y envejecimiento de la piel alrededor de los ojos.
                </p>
                <ul class="list-unstyled text-muted mb-4">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>Elige gafas con filtro <strong>UV400</strong>: bloquean el 99–100 % de los rayos UVA y UVB.</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>Un lente oscuro sin filtro es peor: dilata la pupila y deja entrar más radiación.</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>Los niños son más sensibles: también necesitan protección.</li>
                </ul>
                <a href="{{ route('productos.index', ['uv' => 1]) }}" class="btn btn-gradient rounded-pill px-4">
                    <i class="bi bi-sunglasses me-1"></i> Ver gafas con protección UV
                </a>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <p class="small text-muted mb-1"><i class="bi bi-geo-alt"></i> Índice UV ahora en Ibagué</p>
                    @if ($uv)
                        <div class="display-3 fw-bold" id="uv-valor">{{ $uv['uv'] }}</div>
                        <span class="d-inline-block rounded-pill px-3 py-1 fw-semibold mb-3 mx-auto" id="uv-nivel" style="background-color: {{ $uv['color'] }};">
                            {{ $uv['nivel'] }}
                        </span>
                        <p class="mb-2">{{ $uv['consejo'] }}</p>
                        @if ($uv['uv_max_hoy'] !== null)
                            <p class="small text-muted mb-0">Máximo previsto hoy: <strong>{{ $uv['uv_max_hoy'] }}</strong></p>
                        @endif
                        <p class="small text-muted mt-2 mb-0">Fuente: open-meteo.com · Escala de la OMS</p>
                    @else
                        <p class="my-4 text-muted" id="uv-no-disponible">
                            <i class="bi bi-cloud-slash d-block fs-1 mb-2"></i>
                            No pudimos consultar el índice UV en este momento.
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</section>
