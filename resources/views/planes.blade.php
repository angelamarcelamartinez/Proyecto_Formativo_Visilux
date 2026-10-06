@php $pageTitle = 'Planes'; @endphp
@include('includes.header')

<!-- ======================================================
     BANNER DE LA PÁGINA
====================================================== -->
<section class="page-header">
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Planes y precios</span>
        <h1 class="page-title display-5 mb-3">Nuestros Planes</h1>
        <p class="page-subtitle mx-auto">
            Elige el plan que mejor se adapte al tamaño de tu óptica y accede
            a las funciones que necesitas, cuando las necesites.
        </p>
    </div>
</section>

@include('partials.planes')

@include('includes.footer')