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

<!-- ======================================================
     TERAPIA VISUAL PARA NIÑOS
====================================================== -->
<section class="section-services py-5" style="background: var(--bg-mint);">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">Para Niños</h2>
            <p class="text-muted">Un enfoque lúdico que ayuda a detectar y corregir a tiempo</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card feature-card border shadow-sm h-100 p-4">
                    <div class="feature-icon feature-icon-teal mb-3">
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
                <div class="card feature-card border shadow-sm h-100 p-4">
                    <div class="feature-icon feature-icon-blue mb-3">
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
                <div class="card feature-card border shadow-sm h-100 p-4">
                    <div class="feature-icon feature-icon-teal mb-3">
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
            <div class="col-lg-4">
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

            <div class="col-lg-4">
                <div class="card feature-card border shadow-sm h-100 p-4">
                    <div class="feature-icon feature-icon-teal mb-3">
                        <i class="bi bi-brightness-high"></i>
                    </div>
                    <h3 class="h5 fw-bold">Baja Visión</h3>
                    <p class="text-muted mb-0">
                        Programas de rehabilitación visual para pacientes con pérdida
                        parcial de visión, enfocados en recuperar autonomía diaria.
                    </p>
                </div>
            </div>

            <div class="col-lg-4">
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
