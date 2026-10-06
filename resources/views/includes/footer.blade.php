@include('includes.cookies-banner')

<footer class="site-footer text-white mt-5" id="contacto">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-logo-wrap mb-3">
                    <img src="{{ asset('assets/img/logo-visilux.png') }}" alt="VisiOptica" class="footer-logo">
                </div>
                <p class="footer-text opacity-90">
                    Somos más que una óptica: acompañamos tu salud visual con tecnología, calidez humana y experiencia comprobada.
                </p>
                <div class="d-flex gap-2">
                    <a href="#" class="social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-btn" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
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
                    <li class="mb-2">Lun – Vie: 8:00 – 18:00</li>
                    <li class="mb-2">Sáb: 9:00 – 14:00</li>
                    <li>Dom: Cerrado</li>
                </ul>
            </div>

            <div class="col-lg-2">
                <h5 class="fw-semibold mb-3">Contacto</h5>
                <ul class="list-unstyled footer-text small opacity-90">
                    <li class="mb-2"><i class="bi bi-telephone me-2"></i> +57 300 123 4567</li>
                    <li class="mb-2"><i class="bi bi-envelope me-2"></i> info@visioptica.com</li>
                    <li><i class="bi bi-geo-alt me-2"></i> Bogotá, Colombia</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="footer-bottom text-center py-3">
        <small>
            &copy; {{ date('Y') }} Visilux. Todos los derechos reservados.
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>