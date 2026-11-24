@include('includes.header')

<!-- ======================================================
     SECCIÓN HERO (PORTADA PRINCIPAL)
     Encabezado original. La foto va como <img> (hero-foto) para no usar
     estilos en línea; los estilos están en public/assets/css/style.css.
====================================================== -->
<section class="hero-section d-flex align-items-center" id="inicio">

    <!-- Foto de portada (viene de la base de datos, editable por la óptica) -->
    <img src="{{ $pagina->imagen('hero_imagen') }}" alt="" class="hero-foto">

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
                        <i class="bi bi-calendar-event me-2"></i>
                        Agenda tu Cita
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>

                    <!-- Botón para ir a Conócenos -->
                    <a href="{{ route('conocenos') }}"
                       class="btn btn-ghost btn-lg rounded-pill px-4">
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

            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number">{{ $pagina->stat1_valor }}</span>
                    <span class="stat-label d-block">{{ $pagina->stat1_texto }}</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number">{{ $pagina->stat2_valor }}</span>
                    <span class="stat-label d-block">{{ $pagina->stat2_texto }}</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number">{{ $pagina->stat3_valor }}</span>
                    <span class="stat-label d-block">{{ $pagina->stat3_texto }}</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ======================================================
     TERAPIA VISUAL (resumen que lleva a la página completa)
     Estilos en public/assets/css/style.css → sección "Inicio".
====================================================== -->
<section class="ini-terapia" id="terapia">
    <div class="container">

        <!-- Texto centrado -->
        <div class="ini-terapia-cabecera">
            <span class="ini-eyebrow">Terapia Visual</span>
            <h2 class="ini-terapia-titulo">Entrena tus ojos, mejora tu <span>calidad de vida</span></h2>
            <p class="text-muted">
                Ejercicios y técnicas personalizadas, supervisadas por un optómetra
                especializado, para mejorar la coordinación, el enfoque y la percepción visual
                de niños y adultos.
            </p>
            <div class="ini-terapia-chips">
                <span><i class="bi bi-clock"></i> Sesiones de 1 hora</span>
                <span><i class="bi bi-person-check"></i> Plan personalizado</span>
            </div>
        </div>

        <!-- Cuadro de terapias con dos bloques: niños (colores infantiles) y adultos -->
        <div class="row">
            <div class="col-12">
                <div class="ini-caja ini-caja-abierta">

                    <!-- Tarjeta infantil original (sin cambios) -->
        <div class="card service-card service-card-kids border-0 h-100">

                            <span class="sk-deco sk-d1">☁️</span>
                            <span class="sk-deco sk-d2">⭐</span>
                            <span class="sk-deco sk-d3">🌈</span>
                            <span class="sk-deco sk-d4">🎈</span>
                            <span class="sk-deco sk-d5">💖</span>
                            <span class="sk-sticker">¡Para peques! 🧒</span>

                            <!-- Imagen del servicio con helper asset() -->
                            <div class="sk-photo">
                                <img src="{{ $pagina->imagen('serv1_imagen') }}"
                                     class="service-img"
                                     alt="Optometría Pediátrica">
                            </div>

                            <div class="card-body p-4 sk-body">

                                <!-- Icono -->
                                <div class="service-icon mb-3">
                                    <i class="bi bi-heart-fill"></i>
                                </div>

                                <!-- Título -->
                                <h3 class="h3 fw-bold">
                            Optometría Pediátrica
                        </h3>

                                <!-- Descripción -->
                                <p>
                                    {{ $pagina->serv1_texto }}
                                </p>

                                <!-- Beneficios -->
                                <ul class="sk-chips list-unstyled">
                            <li class="c1">Examen visual completo</li>
                            <li class="c2">Detección temprana de problemas visuales</li>
                            <li class="c3">Asesoría para padres</li>
                        </ul>

                                <a href="{{ route('productos.ninos') }}" class="btn-kids-link">
                                    <i class="bi bi-emoji-smile-fill"></i> Ver gafas para niños
                                </a>

                            </div>

                        </div>

                    <!-- Bloque adultos -->
                    <div class="ini-bloque ini-bloque-adultos">
                        <div class="ini-bloque-foto">
                            <img src="{{ $pagina->imagen('serv2_imagen') }}" alt="Terapia visual para adultos">
                        </div>
                        <div class="ini-bloque-cabecera">
                            <span class="ini-bloque-icono"><i class="bi bi-person"></i></span>
                            <div>
                                <h3>Adultos</h3>
                                <small>Rehabilitación y cuidado</small>
                            </div>
                        </div>
                        <a href="{{ route('terapia-visual') }}" class="ini-fila-terapia dorado">
                            <span class="ini-fila-icono"><i class="bi bi-arrow-repeat"></i></span>
                            <span><b>Ortoqueratología</b><em>Alternativa a la cirugía</em></span>
                        </a>
                        <a href="{{ route('terapia-visual') }}" class="ini-fila-terapia dorado">
                            <span class="ini-fila-icono"><i class="bi bi-laptop"></i></span>
                            <span><b>Fatiga visual digital</b><em>Enfoque y convergencia para pantallas</em></span>
                        </a>
                        <a href="{{ route('terapia-visual') }}" class="ini-bloque-enlace">Ver terapia para adultos <i class="bi bi-arrow-right"></i></a>
                    </div>

                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('terapia-visual') }}" class="btn btn-gradient btn-lg rounded-pill px-4">
                Conoce la terapia visual<i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>

    </div>
</section>

<!-- ======================================================
     RAYOS UV: índice actual (API open-meteo.com, app/Support/ApisExternas.php)
     y por qué usar gafas con protección UV. El botón lleva al catálogo filtrado (/productos?uv=1).
====================================================== -->
@include('includes.rayos-uv', ['uv' => $uv ?? null])

<!-- ======================================================
     PERFIL PROFESIONAL
====================================================== -->
<section class="ini-doctor" id="nosotros">
    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-lg-5">
                <div class="ini-doctor-foto">
                    <img src="{{ $pagina->imagen('prof_imagen') }}" alt="{{ $pagina->prof_nombre }}">
                    <span class="ini-doctor-etiqueta"><i class="bi bi-patch-check-fill me-2"></i>{{ $pagina->prof_cargo }}</span>
                </div>
            </div>

            <div class="col-lg-7">
                <span class="ini-eyebrow ini-eyebrow-claro">Nuestro equipo</span>
                <h2 class="fw-bold mb-2">{{ $pagina->prof_nombre }}</h2>
                <p class="ini-doctor-texto">{{ $pagina->prof_texto }}</p>

                <ul class="ini-credenciales list-unstyled">
                    @foreach ($pagina->credenciales() as $credencial)
                        <li>
                            <span class="ini-cred-icono"><i class="bi bi-mortarboard"></i></span>
                            <div>
                                <strong>{{ $credencial['titulo'] }}</strong>
                                @if ($credencial['lugar'])
                                    <span class="d-block small">{{ $credencial['lugar'] }}</span>
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
<section class="ini-porque">
    <div class="container">

        <div class="text-center mb-5">
            <span class="ini-eyebrow">Por qué elegirnos</span>
            <h2 class="section-title fw-bold">{{ $pagina->porque_titulo }}</h2>
            <p class="text-muted">{{ $pagina->porque_subtitulo }}</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="ini-ventaja">
                    <span class="ini-ventaja-icono"><i class="bi bi-award"></i></span>
                    <div>
                        <h3 class="h5 fw-bold">{{ $pagina->ventaja1_titulo }}</h3>
                        <p class="text-muted mb-0">{{ $pagina->ventaja1_texto }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ini-ventaja">
                    <span class="ini-ventaja-icono"><i class="bi bi-shield-check"></i></span>
                    <div>
                        <h3 class="h5 fw-bold">{{ $pagina->ventaja2_titulo }}</h3>
                        <p class="text-muted mb-0">{{ $pagina->ventaja2_texto }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Llamado final a la acción -->
        <div class="ini-cta">
            <div>
                <h3 class="fw-bold mb-1">¿Listo para cuidar tu visión?</h3>
                <p class="mb-0">Agenda una cita y te atendemos con gusto.</p>
            </div>
            <a href="{{ route('citas.create') }}" class="btn btn-lg rounded-pill px-5 ini-cta-btn">
                <i class="bi bi-calendar-event me-2"></i>Agenda tu Cita
            </a>
        </div>
    </div>
</section>

@include('includes.footer')
