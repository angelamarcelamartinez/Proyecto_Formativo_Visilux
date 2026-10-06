@include('includes.header')

<!-- ======================================================
     SECCIÓN HERO (PORTADA PRINCIPAL)
====================================================== -->
<section class="hero-section d-flex align-items-center" id="inicio"
         style="background-image: url('{{ $pagina->imagen('hero_imagen') }}');">

    <!-- Capa oscura/transparente sobre la imagen de fondo -->
    <div class="hero-overlay"></div>

    <!-- Contenedor principal -->
    <div class="container position-relative z-1">

        <!-- Fila Bootstrap -->
        <div class="row">

            <!-- Columna donde va el texto principal -->
            <div class="col-lg-7 col-xl-6">

                <!-- Título principal -->
                <h1 class="hero-title mb-3">

                    <!-- Texto con degradado -->
                    <span class="text-gradient d-block">
                        {{ $pagina->hero_titulo }}
                    </span>

                    <!-- Texto principal en blanco -->
                    <span class="text-white display-3 fw-bold">
                        {{ $pagina->hero_subtitulo }}
                    </span>

                </h1>

                <!-- Descripción principal -->
                <p class="hero-subtitle text-white mb-4">
                    {{ $pagina->hero_texto }}
                </p>

                <!-- Contenedor de botones -->
                <div class="d-flex flex-wrap gap-3">

                    <!-- Botón para ir a agendar cita -->
                    <a href="{{ route('citas.create') }}"
                       class="btn btn-gradient btn-lg rounded-pill px-4">

                        <!-- Icono calendario -->
                        <i class="bi bi-calendar-event me-2"></i>

                        Agenda tu Cita

                        <!-- Icono flecha -->
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>

                    <!-- Botón para ir a Conócenos -->
                    <a href="{{ route('conocenos') }}"
                       class="btn btn-ghost btn-lg rounded-pill px-4">

                        <!-- Icono corazón -->
                        <i class="bi bi-heart me-2"></i>

                        Conoce Más
                    </a>

                </div>

            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     BARRA DE ESTADÍSTICAS
====================================================== -->
<section class="stats-bar py-4">

    <div class="container">

        <div class="row text-center g-4">

            <!-- Estadística 1 -->
            <div class="col-md-4">

                <div class="stat-item">

                    <!-- Número destacado -->
                    <span class="stat-number">{{ $pagina->stat1_valor }}</span>

                    <!-- Descripción -->
                    <span class="stat-label d-block">
                        {{ $pagina->stat1_texto }}
                    </span>

                </div>

            </div>

            <!-- Estadística 2 -->
            <div class="col-md-4">

                <div class="stat-item">

                    <span class="stat-number">{{ $pagina->stat2_valor }}</span>

                    <span class="stat-label d-block">
                        {{ $pagina->stat2_texto }}
                    </span>

                </div>

            </div>

            <!-- Estadística 3 -->
            <div class="col-md-4">

                <div class="stat-item">

                    <span class="stat-number">{{ $pagina->stat3_valor }}</span>

                    <span class="stat-label d-block">
                        {{ $pagina->stat3_texto }}
                    </span>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- ======================================================
     SECCIÓN DE SERVICIOS
====================================================== -->
<section class="section-services py-5" id="servicios">

    <div class="container py-4">

        <!-- Encabezado -->
        <div class="text-center mb-5">

            <h2 class="section-title fw-bold">
                {{ $pagina->servicios_titulo }}
            </h2>

            <p class="text-muted">
                {{ $pagina->servicios_subtitulo }}
            </p>

        </div>

        <!-- Fila de servicios -->
        <div class="row g-4" id="productos">

            <!-- ==========================================
                 SERVICIO 1
            =========================================== -->
            <div class="col-lg-6">

                <div class="card service-card border-0 shadow-sm h-100">

                    <!-- Imagen del servicio con helper asset() -->
                    <img src="{{ $pagina->imagen('serv1_imagen') }}"
                         class="card-img-top service-img"
                         alt="{{ $pagina->serv1_titulo }}">

                    <div class="card-body p-4">

                        <!-- Icono -->
                        <div class="service-icon mb-3">
                            <i class="bi bi-heart-fill"></i>
                        </div>

                        <!-- Título -->
                        <h3 class="h4 fw-bold text-heading">
                            {{ $pagina->serv1_titulo }}
                        </h3>

                        <!-- Descripción -->
                        <p class="text-muted">
                            {{ $pagina->serv1_texto }}
                        </p>

                        <!-- Beneficios -->
                        <ul class="service-list list-unstyled">
                            @foreach ($pagina->lineas('serv1_items') as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>

                    </div>

                </div>

            </div>

            <!-- ==========================================
                 SERVICIO 2
            =========================================== -->
            <div class="col-lg-6">

                <div class="card service-card border-0 shadow-sm h-100">

                    <!-- Imagen con helper asset() -->
                    <img src="{{ $pagina->imagen('serv2_imagen') }}"
                         class="card-img-top service-img service-img-bw"
                         alt="{{ $pagina->serv2_titulo }}">

                    <div class="card-body p-4">

                        <!-- Icono -->
                        <div class="service-icon mb-3">
                            <i class="bi bi-eye-fill"></i>
                        </div>

                        <!-- Nombre servicio -->
                        <h3 class="h4 fw-bold text-heading">
                            {{ $pagina->serv2_titulo }}
                        </h3>

                        <!-- Descripción -->
                        <p class="text-muted">
                            {{ $pagina->serv2_texto }}
                        </p>

                        <!-- Beneficios -->
                        <ul class="service-list list-unstyled">
                            @foreach ($pagina->lineas('serv2_items') as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- ======================================================
     PERFIL PROFESIONAL
====================================================== -->
<section class="section-doctor py-5" id="nosotros">

    <div class="container py-4">

        <div class="row align-items-center g-5">

            <!-- Imagen de la doctora con helper asset() -->
            <div class="col-lg-5">

                <img src="{{ $pagina->imagen('prof_imagen') }}"
                     alt="{{ $pagina->prof_nombre }}"
                     class="img-fluid doctor-photo shadow">

            </div>

            <!-- Información -->
            <div class="col-lg-7">

                <h2 class="fw-bold text-heading mb-1">
                    {{ $pagina->prof_nombre }}
                </h2>

                <p class="text-teal fw-semibold mb-3">
                    {{ $pagina->prof_cargo }}
                </p>

                <p class="text-muted mb-4">
                    {{ $pagina->prof_texto }}
                </p>

                <!-- Lista de estudios -->
                <ul class="credentials-list list-unstyled">

                    @foreach ($pagina->credenciales() as $credencial)
                        <li>
                            <span class="credential-dot"></span>

                            <div>
                                <strong>{{ $credencial['titulo'] }}</strong>

                                @if ($credencial['lugar'])
                                    <span class="d-block text-muted small">
                                        {{ $credencial['lugar'] }}
                                    </span>
                                @endif
                            </div>
                        </li>
                    @endforeach

                </ul>

            </div>

        </div>

    </div>

</section>

<!-- ======================================================
     ¿POR QUÉ ELEGIRNOS?
====================================================== -->
<section class="section-why py-5 bg-white">

    <div class="container py-4">

        <div class="text-center mb-5">

            <h2 class="section-title text-teal fw-bold">
                {{ $pagina->porque_titulo }}
            </h2>

            <p class="text-muted">
                {{ $pagina->porque_subtitulo }}
            </p>

        </div>

        <!-- Tarjetas de ventajas -->
        <div class="row g-4">

            <!-- Ventaja 1 -->
            <div class="col-md-6">
                <div class="card feature-card border shadow-sm h-100 p-4">

                    <div class="feature-icon feature-icon-teal mb-3">
                        <i class="bi bi-award"></i>
                    </div>

                    <h3 class="h5 fw-bold">
                        {{ $pagina->ventaja1_titulo }}
                    </h3>

                    <p class="text-muted mb-0">
                        {{ $pagina->ventaja1_texto }}
                    </p>

                </div>
            </div>

            <!-- Ventaja 2 -->
            <div class="col-md-6">
                <div class="card feature-card border shadow-sm h-100 p-4">

                    <div class="feature-icon feature-icon-blue mb-3">
                        <i class="bi bi-shield-check"></i>
                    </div>

                    <h3 class="h5 fw-bold">
                        {{ $pagina->ventaja2_titulo }}
                    </h3>

                    <p class="text-muted mb-0">
                        {{ $pagina->ventaja2_texto }}
                    </p>

                </div>
            </div>

        </div>

    </div>

</section>

@include('includes.footer')