@include('includes.header')

<!-- ======================================================
     SECCIÓN HERO (PORTADA PRINCIPAL)
====================================================== -->
<section class="hero-section d-flex align-items-center" id="inicio">

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
                        Bienvenidos
                    </span>

                    <!-- Texto principal en blanco -->
                    <span class="text-white display-3 fw-bold">
                        a tu salud visual
                    </span>

                </h1>

                <!-- Descripción principal -->
                <p class="hero-subtitle text-white mb-4">
                    Cuidamos de tus ojos con tecnología avanzada,
                    atención personalizada y años de experiencia.
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
                    <span class="stat-number">15+</span>

                    <!-- Descripción -->
                    <span class="stat-label d-block">
                        Años de experiencia
                    </span>

                </div>

            </div>

            <!-- Estadística 2 -->
            <div class="col-md-4">

                <div class="stat-item">

                    <span class="stat-number">5000+</span>

                    <span class="stat-label d-block">
                        Pacientes felices
                    </span>

                </div>

            </div>

            <!-- Estadística 3 -->
            <div class="col-md-4">

                <div class="stat-item">

                    <span class="stat-number">100%</span>

                    <span class="stat-label d-block">
                        Atención personalizada
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
                Nuestros Servicios
            </h2>

            <p class="text-muted">
                Atención especializada para todas las edades
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
                    <img src="{{ asset('assets/img/niños.jpg') }}"
                         class="card-img-top service-img"
                         alt="Optometría infantil">

                    <div class="card-body p-4">

                        <!-- Icono -->
                        <div class="service-icon mb-3">
                            <i class="bi bi-heart-fill"></i>
                        </div>

                        <!-- Título -->
                        <h3 class="h4 fw-bold text-heading">
                            Optometría Infantil
                        </h3>

                        <!-- Descripción -->
                        <p class="text-muted">
                            Evaluaciones adaptadas para niños con un enfoque
                            lúdico y profesional que genera confianza.
                        </p>

                        <!-- Beneficios -->
                        <ul class="service-list list-unstyled">
                            <li>Examen visual completo</li>
                            <li>Detección temprana de problemas</li>
                            <li>Asesoría para padres</li>
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
                    <img src="{{ asset('assets/img/adultos.jpg') }}"
                         class="card-img-top service-img service-img-bw"
                         alt="Terapia visual">

                    <div class="card-body p-4">

                        <!-- Icono -->
                        <div class="service-icon mb-3">
                            <i class="bi bi-eye-fill"></i>
                        </div>

                        <!-- Nombre servicio -->
                        <h3 class="h4 fw-bold text-heading">
                            Terapia Visual
                        </h3>

                        <!-- Descripción -->
                        <p class="text-muted">
                            Programas personalizados para mejorar la coordinación
                            ocular, enfoque y rendimiento visual.
                        </p>

                        <!-- Beneficios -->
                        <ul class="service-list list-unstyled">
                            <li>Ejercicios visuales guiados</li>
                            <li>Seguimiento periódico</li>
                            <li>Equipos de última generación</li>
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

                <img src="{{ asset('assets/img/optometra_1.jpg') }}"
                     alt="Dra. María Fernández"
                     class="img-fluid doctor-photo shadow">

            </div>

            <!-- Información -->
            <div class="col-lg-7">

                <h2 class="fw-bold text-heading mb-1">
                    Dra. Catalina Fernandez
                </h2>

                <p class="text-teal fw-semibold mb-3">
                    Optómetra Especializada
                </p>

                <p class="text-muted mb-4">
                    Con más de 15 años de experiencia, la Dra. Esquivel
                    combina precisión clínica con un trato cercano.
                </p>

                <!-- Lista de estudios -->
                <ul class="credentials-list list-unstyled">

                    <li>
                        <span class="credential-dot"></span>

                        <div>
                            <strong>Optometría Certificada</strong>

                            <span class="d-block text-muted small">
                                Universidad Nacional de Colombia
                            </span>
                        </div>
                    </li>

                    <li>
                        <span class="credential-dot"></span>

                        <div>
                            <strong>Terapia Visual Avanzada</strong>

                            <span class="d-block text-muted small">
                                Instituto de Salud Visual
                            </span>
                        </div>
                    </li>

                    <li>
                        <span class="credential-dot"></span>

                        <div>
                            <strong>Pediatría y Salud Ocular</strong>

                            <span class="d-block text-muted small">
                                Colegio Colombiano de Optómetras
                            </span>
                        </div>
                    </li>

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
                ¿Por qué elegirnos?
            </h2>

            <p class="text-muted">
                Somos más que una óptica, somos tu aliado en salud visual
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
                        Experiencia Comprobada
                    </h3>

                    <p class="text-muted mb-0">
                        Más de 15 años cuidando la visión.
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
                        Tecnología Avanzada
                    </h3>

                    <p class="text-muted mb-0">
                        Equipos modernos para diagnósticos precisos.
                    </p>

                </div>
            </div>

        </div>

    </div>

</section>

@include('includes.footer')