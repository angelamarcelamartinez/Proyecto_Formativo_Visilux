@php $pageTitle = 'Conócenos'; @endphp
@include('includes.header')


<!-- ======================================================
     BANNER DE LA PÁGINA
====================================================== -->
<section class="page-header">
    <span class="ph-ring r1"></span><span class="ph-ring r2"></span><span class="ph-ring r3"></span>
    <div class="container text-center">
        <span class="page-eyebrow mb-3">Sobre nosotros</span>
        <h1 class="page-title display-4 mb-3">Conócenos</h1>
        <p class="page-subtitle mx-auto">
            Más de 15 años cuidando la salud visual de miles de familias, con tecnología
            de punta y un equipo humano comprometido con tu bienestar.
        </p>
    </div>
</section>

<!-- Cifras rápidas -->
<div class="container cn-stats">
    <div class="cn-stats-box reveal">
        <div class="cn-stat"><b>+15</b><span>años de experiencia</span></div>
        <div class="cn-stat"><b>Miles</b><span>de familias atendidas</span></div>
        <div class="cn-stat"><b>2030</b><span>meta: líder regional</span></div>
    </div>
</div>

<!-- ======================================================
     MISIÓN Y VISIÓN
====================================================== -->
<section class="py-5">
    <div class="container py-4">
        <div class="row g-4 reveal-grupo">
            <div class="col-md-6">
                <div class="card feature-card cn-card h-100 p-4">
                    <div class="feature-icon feature-icon-teal mb-3">
                        <i class="bi bi-bullseye"></i>
                    </div>
                    <h3 class="h4 fw-bold text-heading">Misión</h3>
                    <p class="text-muted mb-0">
                        Brindar servicios de salud visual integrales, accesibles y de alta
                        calidad, combinando tecnología avanzada con atención cálida y
                        personalizada, para mejorar la calidad de vida de nuestros pacientes
                        en cada etapa de su vida.
                    </p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card feature-card cn-card h-100 p-4">
                    <div class="feature-icon feature-icon-blue mb-3">
                        <i class="bi bi-binoculars"></i>
                    </div>
                    <h3 class="h4 fw-bold text-heading">Visión</h3>
                    <p class="text-muted mb-0">
                        Ser reconocidos en el 2030 como la óptica y centro de terapia visual
                        líder de la región, referente en innovación, formación continua y
                        cercanía con la comunidad que atendemos.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     VALORES
====================================================== -->
<section class="cn-valores">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="cn-eyebrow">Lo que nos define</span>
            <h2 class="section-title fw-bold">Nuestros Valores</h2>
        </div>

        <div class="row g-4 text-center reveal-grupo">
            <div class="col-md-4">
                <div class="cn-valor">
                <div class="feature-icon feature-icon-teal mx-auto mb-3">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <h3 class="h5 fw-bold">Calidez Humana</h3>
                <p class="text-muted">Cada paciente recibe atención cercana y personalizada.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cn-valor">
                <div class="feature-icon feature-icon-blue mx-auto mb-3">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3 class="h5 fw-bold">Excelencia</h3>
                <p class="text-muted">Procesos clínicos rigurosos y tecnología confiable.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cn-valor">
                <div class="feature-icon feature-icon-teal mx-auto mb-3">
                    <i class="bi bi-award"></i>
                </div>
                <h3 class="h5 fw-bold">Compromiso</h3>
                <p class="text-muted">Acompañamos a nuestros pacientes en todo su proceso.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     MAPA Y FORMULARIO DE CONTACTO
====================================================== -->
<section class="py-5 cn-contacto">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="cn-eyebrow">Hablemos</span>
            <h2 class="section-title fw-bold">Contáctanos</h2>
            <p class="text-muted">Escríbenos o visítanos, con gusto te atendemos</p>
        </div>

        <div class="row g-5">
            <!-- Mapa -->
            <div class="col-lg-6">
                <iframe
                    class="map-frame"
                    src="https://www.google.com/maps?q=Bogot%C3%A1,+Colombia&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>

                <div class="cn-info">
                    <div class="cn-info-item"><i class="bi bi-geo-alt"></i> Bogotá, Colombia</div>
                    <div class="cn-info-item"><i class="bi bi-telephone"></i> +57 300 123 4567</div>
                    <div class="cn-info-item"><i class="bi bi-envelope"></i> info@visioptica.com</div>
                </div>
            </div>

            <!-- Formulario -->
            <div class="col-lg-6">
                @if (session('contacto_enviado'))
                    <div class="alert alert-success-soft p-3 mb-4">
                        <i class="bi bi-check-circle me-2"></i>
                        ¡Gracias por escribirnos! Te responderemos muy pronto.
                    </div>
                @endif

                <form method="POST" action="{{ route('conocenos.contacto') }}" novalidate data-validar class="cn-form">
                    @csrf

                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-medium">Nombre</label>
                        <input type="text"
                               class="form-control @error('nombre') is-invalid @enderror"
                               id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="150" data-v="nombre" required>
                        @error('nombre')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label fw-medium">Correo electrónico</label>
                        <input type="email"
                               class="form-control @error('correo') is-invalid @enderror"
                               id="correo" name="correo" value="{{ old('correo') }}" maxlength="50" data-v="email" required>
                        @error('correo')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-medium">Teléfono (opcional)</label>
                        <input type="text"
                               class="form-control @error('telefono') is-invalid @enderror"
                               id="telefono" name="telefono" value="{{ old('telefono') }}" maxlength="10" inputmode="tel" placeholder="300 123 4567" data-v="telefono">
                        @error('telefono')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="mensaje" class="form-label fw-medium">Mensaje</label>
                        <textarea class="form-control @error('mensaje') is-invalid @enderror"
                                  id="mensaje" name="mensaje" rows="4" minlength="10" maxlength="2000" required>{{ old('mensaje') }}</textarea>
                        @error('mensaje')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-gradient rounded-pill px-4">
                        <i class="bi bi-send me-2"></i> Enviar mensaje
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

@include('includes.footer')
