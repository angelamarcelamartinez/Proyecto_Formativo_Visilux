@php $pageTitle = 'Planes'; @endphp
@include('includes.header')

<!-- ======================================================
     BANNER DE LA PÁGINA
====================================================== -->
<section class="page-header">
    <span class="ph-ring r1"></span><span class="ph-ring r2"></span><span class="ph-ring r3"></span>
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Planes y precios</span>
        <h1 class="page-title display-5 mb-3">Nuestros Planes</h1>
        <p class="page-subtitle mx-auto">
            Elige el plan que mejor se adapte al tamaño de tu óptica y accede
            a las funciones que necesitas, cuando las necesites.
        </p>
        <div class="ph-chips">
            <span class="ph-chip"><i class="bi bi-shop"></i> Para cada óptica</span>
            <span class="ph-chip"><i class="bi bi-arrow-repeat"></i> Renovable</span>
            <span class="ph-chip"><i class="bi bi-headset"></i> Soporte</span>
        </div>
    </div>
</section>

@include('partials.planes')

@include('includes.footer')