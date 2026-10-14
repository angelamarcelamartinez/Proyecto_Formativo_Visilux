@include('includes.cookies-banner')

<footer class="site-footer text-white mt-5" id="contacto">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-logo-wrap mb-3">
                    <img src="{{ $sitio?->imagen('logo') ?? asset('assets/img/logo-visilux.png') }}" alt="{{ $sitioEmpresa->nombre ?? 'VisiOptica' }}" class="footer-logo">
                </div>
                <p class="footer-text opacity-90">
                    {{ $sitio->footer_texto ?? '' }}
                </p>
                <div class="d-flex gap-2">
                    @if ($sitio?->instagram)
                        <a href="{{ $sitio->instagram }}" target="_blank" rel="noopener" class="social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    @endif
                    @if ($sitio?->facebook)
                        <a href="{{ $sitio->facebook }}" target="_blank" rel="noopener" class="social-btn" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <h5 class="fw-semibold mb-3">Suscríbete a nuestro boletín</h5>
                <p class="footer-text small opacity-90 mb-3">
                    Recibe consejos de salud visual y promociones exclusivas.
                </p>
                <form class="newsletter-form d-flex gap-2" action="#" method="post" onsubmit="return false;">
                    <input type="email" class="form-control rounded-pill border-0" placeholder="correo electrónico" required>
                    <button type="submit" class="btn btn-light rounded-pill px-4 fw-semibold text-primary flex-shrink-0">
                        Suscribirse
                    </button>
                </form>
            </div>

            <div class="col-lg-2">
                <h5 class="fw-semibold mb-3">Horario</h5>
                <ul class="list-unstyled footer-text small opacity-90">
                    @foreach ($sitio ? $sitio->lineas('horario') : [] as $linea)
                        <li class="{{ $loop->last ? '' : 'mb-2' }}">{{ $linea }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="col-lg-2">
                <h5 class="fw-semibold mb-3">Contacto</h5>
                <ul class="list-unstyled footer-text small opacity-90">
                    @if ($sitio?->telefono)
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i> {{ $sitio->telefono }}</li>
                    @endif
                    @if ($sitio?->email)
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> {{ $sitio->email }}</li>
                    @endif
                    @if ($sitio?->direccion)
                        <li><i class="bi bi-geo-alt me-2"></i> {{ $sitio->direccion }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="footer-bottom text-center py-3">
        <small>
            &copy; {{ date('Y') }} {{ $sitioEmpresa->nombre ?? 'Visilux' }}. Todos los derechos reservados.
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>