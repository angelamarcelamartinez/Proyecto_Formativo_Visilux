@php $pageTitle = 'Terapia Visual'; @endphp
@include('includes.header')

<!-- ======================================================
     BANNER DE LA PÁGINA
====================================================== -->
<section class="page-header">
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Salud visual funcional</span>
        <h1 class="page-title display-5 mb-3">Terapia Visual</h1>
        <p class="page-subtitle mx-auto">
            Programas de rehabilitación y entrenamiento visual diseñados para niños
            y adultos, enfocados en mejorar la coordinación, el enfoque y la calidad
            de vida de nuestros pacientes.
        </p>
    </div>
</section>

<!-- ======================================================
     INTRODUCCIÓN
====================================================== -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h2 class="section-title fw-bold">¿Qué es la Terapia Visual?</h2>
                <p class="text-muted">
                    Es un conjunto de ejercicios y técnicas personalizadas, supervisadas
                    por un optómetra especializado, que buscan corregir o mejorar
                    problemas de coordinación ocular, enfoque, percepción visual y
                    binocularidad que no siempre se resuelven solo con lentes.
                </p>
            </div>
        </div>
    </div>
</section>


<style>
    /* Sección infantil: colores vivos y figuras geométricas */
    .ninos-seccion { position: relative; overflow: hidden; background: linear-gradient(180deg, #fff8e1 0%, #e8f7ff 100%); }
    .ninos-figura { position: absolute; opacity: .28; z-index: 0; pointer-events: none; }
    .ninos-f1 { top: 28px; left: 6%; font-size: 3rem; color: #ffb300; transform: rotate(-12deg); }
    .ninos-f2 { top: 120px; right: 8%; font-size: 3.4rem; color: #29b6f6; }
    .ninos-f3 { bottom: 50px; left: 10%; font-size: 3rem; color: #ef5350; transform: rotate(10deg); }
    .ninos-f4 { bottom: 70px; right: 6%; font-size: 2.6rem; color: #66bb6a; transform: rotate(14deg); }
    .ninos-duracion { background: #ffb300; color: #fff; font-weight: 600; padding: .5rem .9rem; }
    .ninos-card { border-radius: 1.5rem; border-top: 8px solid var(--c) !important; }
    .ninos-rojo { --c: #ef5350; }
    .ninos-azul { --c: #29b6f6; }
    .ninos-amarillo { --c: #ffb300; }
    .ninos-icono { width: 64px; height: 64px; border-radius: 50% 50% 50% 14px; background: var(--c); color: #fff; font-size: 1.8rem;
        display: flex; align-items: center; justify-content: center; }
    .ninos-card h3 { color: var(--c); }
</style>

<!-- ======================================================
     TERAPIA VISUAL PARA NIÑOS
====================================================== -->
<section class="section-services py-5 ninos-seccion">
    <span class="ninos-figura ninos-f1"><i class="bi bi-star-fill"></i></span>
    <span class="ninos-figura ninos-f2"><i class="bi bi-circle-fill"></i></span>
    <span class="ninos-figura ninos-f3"><i class="bi bi-triangle-fill"></i></span>
    <span class="ninos-figura ninos-f4"><i class="bi bi-square-fill"></i></span>
    <div class="container py-4 position-relative">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">Para Niños</h2>
            <p class="text-muted mb-2">Un enfoque lúdico que ayuda a detectar y corregir a tiempo</p>
            <span class="badge rounded-pill ninos-duracion"><i class="bi bi-clock me-1"></i>Cada sesión de terapia dura 1 hora</span>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card feature-card ninos-card ninos-rojo border-0 shadow-sm h-100 p-4">
                    <div class="feature-icon ninos-icono mb-3">
                        <i class="bi bi-eye"></i>
                    </div>
                    <h3 class="h5 fw-bold">Ambliopía (Ojo Perezoso)</h3>
                    <p class="text-muted mb-0">
                        Ejercicios de oclusión y estimulación visual para fortalecer el ojo
                        con menor agudeza y recuperar la visión binocular.
                    </p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card feature-card ninos-card ninos-azul border-0 shadow-sm h-100 p-4">
                    <div class="feature-icon ninos-icono mb-3">
                        <i class="bi bi-puzzle"></i>
                    </div>
                    <h3 class="h5 fw-bold">Coordinación Ojo-Mano</h3>
                    <p class="text-muted mb-0">
                        Actividades dirigidas para niños con dificultades de lectura,
                        escritura o rendimiento escolar relacionadas con la visión.
                    </p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card feature-card ninos-card ninos-amarillo border-0 shadow-sm h-100 p-4">
                    <div class="feature-icon ninos-icono mb-3">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <h3 class="h5 fw-bold">Binocularidad</h3>
                    <p class="text-muted mb-0">
                        Corrección de problemas para usar ambos ojos de forma coordinada,
                        clave para el desarrollo visual temprano.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     TERAPIA VISUAL PARA ADULTOS
====================================================== -->
<section class="section-services py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">Para Adultos</h2>
            <p class="text-muted">Rehabilitación y cuidado visual en cada etapa de la vida</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card feature-card border shadow-sm h-100 p-4">
                    <div class="feature-icon feature-icon-blue mb-3">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <h3 class="h5 fw-bold">Ortoqueratología</h3>
                    <p class="text-muted mb-0">
                        Remodelación corneal nocturna con lentes especiales, una
                        alternativa a la cirugía para reducir la dependencia de lentes.
                    </p>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card feature-card border shadow-sm h-100 p-4">
                    <div class="feature-icon feature-icon-blue mb-3">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h3 class="h5 fw-bold">Fatiga Visual Digital</h3>
                    <p class="text-muted mb-0">
                        Ejercicios de enfoque y convergencia para adultos con molestias
                        por el uso prolongado de pantallas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     LLAMADO A LA ACCIÓN
====================================================== -->
<section class="py-5 text-center">
    <div class="container">
        <h2 class="section-title fw-bold mb-3">¿Listo para empezar tu terapia visual?</h2>
        <p class="text-muted mb-4">Agenda una evaluación inicial con nuestra optómetra especializada.</p>
        <a href="{{ route('citas.create') }}" class="btn btn-gradient btn-lg rounded-pill px-5">
            <i class="bi bi-calendar-event me-2"></i> Agenda tu Cita
        </a>
    </div>
</section>

@include('includes.footer')
