@php
    /*
     * Detalle de funcionalidades para la sección "¿Qué incluye cada plan?".
     * Para agregar o quitar algo, edita esta lista.
     */
    $modulos = [
        [
            'icon'  => 'bi-calendar2-check',
            'titulo' => 'Agenda y citas',
            'resumen' => 'Agenda en línea y horarios de optómetras',
            'detalle' => [
                'Agendamiento de citas desde la página web',
                'Horarios por optómetra y especialidad',
                'Tipos y motivos de cita configurables',
                'Vista de citas del día, la semana o el mes',
            ],
        ],
        [
            'icon'  => 'bi-envelope-paper-heart',
            'titulo' => 'Recordatorios de control',
            'resumen' => 'Correos automáticos para controles anuales',
            'detalle' => [
                'Detecta pacientes que cumplen un año desde su última cita',
                'Envío de recordatorios por correo, uno a uno o masivo',
            ],
        ],
        [
            'icon'  => 'bi-clipboard2-pulse',
            'titulo' => 'Historias clínicas',
            'resumen' => 'Diagnósticos y fórmulas médicas',
            'detalle' => [
                'Historia clínica por paciente',
                'Diagnósticos y enfermedades',
                'Fórmulas médicas con medicamentos y procedimientos',
            ],
        ],
        [
            'icon'  => 'bi-eye',
            'titulo' => 'Terapia visual',
            'resumen' => 'Sesiones y evolución de cada paciente',
            'detalle' => [
                'Tipos de terapia y procedimientos',
                'Agendamiento de sesiones',
                'Registro de la evolución de cada terapia',
            ],
        ],
        [
            'icon'  => 'bi-eyeglasses',
            'titulo' => 'Catálogo y tienda en línea',
            'resumen' => 'Productos con carrito de compras',
            'detalle' => [
                'Productos con marca, modelo, talla, precio e imagen',
                'Categorías y tipos de producto',
                'Carrito de compras para clientes registrados e invitados',
            ],
        ],
        [
            'icon'  => 'bi-box-seam',
            'titulo' => 'Inventario',
            'resumen' => 'Lotes y alertas de stock bajo',
            'detalle' => [
                'Control de inventario por lotes',
                'Alertas de productos con stock bajo',
                'Inventario de medicamentos',
            ],
        ],
        [
            'icon'  => 'bi-truck',
            'titulo' => 'Proveedores y envíos',
            'resumen' => 'Pedidos a proveedores y seguimiento de envíos',
            'detalle' => [
                'Registro de proveedores y sus productos',
                'Pedidos a proveedores y su estado',
                'Envíos con transportadora y seguimiento',
            ],
        ],
        [
            'icon'  => 'bi-receipt',
            'titulo' => 'Ventas',
            'resumen' => 'Ventas de productos y medicamentos',
            'detalle' => [
                'Ventas en mostrador y compras en línea',
                'Ventas de medicamentos',
                'Resumen de ventas del día en el panel',
            ],
        ],
        [
            'icon'  => 'bi-speedometer2',
            'titulo' => 'Panel de administración',
            'resumen' => 'Indicadores del día y gestión completa',
            'detalle' => [
                'Dashboard con citas, ventas, pedidos y stock',
                'Gestión de usuarios, roles y optómetras',
                'Buscador rápido de todas las tablas del sistema',
            ],
        ],
    ];
@endphp

{{-- Estilos de esta sección: van aquí para no depender de style.css ni del caché --}}
<style>
    .section-plans .plan-billing { font-size: .8rem; color: #6B6B6B; }

    .section-plans .plan-includes-title {
        font-size: .75rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: #B08D3E;
        padding-top: 1rem; border-top: 1px solid #eee;
    }

    .section-plans .plan-more { font-size: .85rem; font-weight: 600; color: #B08D3E; text-decoration: none; }
    .section-plans .plan-more:hover { color: #3A3A3A; text-decoration: underline; }

    .section-plans .plan-includes { scroll-margin-top: 90px; }
    .section-plans .plan-includes-lead { max-width: 560px; }

    .section-plans .plan-eyebrow {
        font-size: .75rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .12em; color: #B08D3E;
    }

    .section-plans .plan-feature-card {
        background: #fff;
        border: 1px solid #ece6d8;
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
        transition: transform .2s, box-shadow .2s, border-color .2s;
    }
    .section-plans .plan-feature-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08);
        border-color: #D4AF6A;
    }

    .section-plans .plan-feature-icon {
        width: 44px; height: 44px; flex-shrink: 0;
        border-radius: 12px;
        background: #F7F3EB;
        color: #B08D3E;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
    }
    .section-plans .plan-feature-icon i { color: #B08D3E; }

    .section-plans .plan-feature-card .plan-features li {
        display: flex; align-items: flex-start; gap: .5rem;
        padding: .25rem 0; font-size: .88rem; color: #444;
    }
    .section-plans .plan-features li i.bi-check2 { color: #B08D3E; }
</style>

<!-- ======================================================
     SECCIÓN DE PLANES
====================================================== -->
<section class="section-plans py-5" id="planes">

    <div class="container py-4">

        <!-- Fila de planes -->
        <div class="row g-4 justify-content-center align-items-stretch">

            <!-- ==========================================
                 PLAN 1 MES
            =========================================== -->
            <div class="col-lg-3 col-md-6">

                <div class="card plan-card border shadow-sm h-100 p-4">

                    <h3 class="h5 fw-bold plan-name mb-1">
                        FREE 1 mes
                    </h3>

                    <p class="text-muted small mb-3">
                        Prueba mensual
                    </p>

                    <div class="plan-price mb-4">
                        <span class="plan-price-amount">0</span>
                        <span class="plan-price-period">/ mes</span>
                    </div>

                    <p class="plan-includes-title mb-2">
                        <i class="bi bi-stars"></i> Todo Incluido
                    </p>

                    <ul class="plan-features list-unstyled mb-3 flex-grow-1">
                        <li><i class="bi bi-check2"></i>Agenda y citas</li>
                        <li><i class="bi bi-check2"></i>Recordatorios de control</li>
                        <li><i class="bi bi-check2"></i>Historias clínicas</li>
                        <li><i class="bi bi-check2"></i>Terapia visual</li>
                        <li><i class="bi bi-check2"></i>Catálogo y tienda en línea</li>
                    </ul>

                    <a href="#que-incluye" class="plan-more mb-4">+ 4 funcionalidades más</a>

                    <a href="{{ route('register') }}"
                       class="btn btn-plan-outline rounded-pill w-100">
                        Elegir plan
                    </a>

                </div>

            </div>

            <!-- ==========================================
                 PLAN 6 MESES (Destacado)
            =========================================== -->
            <div class="col-lg-3 col-md-6">

                <div class="card plan-card plan-card-featured border-0 shadow-sm h-100 p-4">

                    <span class="plan-badge">Más popular</span>

                    <h3 class="h5 fw-bold plan-name mb-1">
                        6 meses
                    </h3>

                    <div class="plan-price mb-1">
                        <span class="plan-price-amount">$299.999</span>
                        <span class="plan-price-period">/ mes</span>
                    </div>

                    <p class="plan-billing mb-4">Facturado cada 6 meses</p>

                    <p class="plan-includes-title mb-2">
                        <i class="bi bi-stars"></i> Todo Incluido
                    </p>

                    <ul class="plan-features list-unstyled mb-3 flex-grow-1">
                        <li><i class="bi bi-check2"></i>Agenda y citas</li>
                        <li><i class="bi bi-check2"></i>Recordatorios de control</li>
                        <li><i class="bi bi-check2"></i>Historias clínicas</li>
                        <li><i class="bi bi-check2"></i>Terapia visual</li>
                        <li><i class="bi bi-check2"></i>Catálogo y tienda en línea</li>
                    </ul>

                    <a href="#que-incluye" class="plan-more mb-4">+ 4 funcionalidades más</a>

                    <a href="{{ route('register') }}"
                       class="btn btn-plan rounded-pill w-100">
                        Elegir plan
                    </a>

                </div>

            </div>

            <!-- ==========================================
                 PLAN 9 MESES
            =========================================== -->
            <div class="col-lg-3 col-md-6">

                <div class="card plan-card border shadow-sm h-100 p-4">

                    <h3 class="h5 fw-bold plan-name mb-1">
                        9 meses
                    </h3>

                    <div class="plan-price mb-1">
                        <span class="plan-price-amount">$449.999</span>
                        <span class="plan-price-period">/ mes</span>
                    </div>

                    <p class="plan-billing mb-4">Facturado cada 9 meses</p>

                    <p class="plan-includes-title mb-2">
                        <i class="bi bi-stars"></i> Todo Incluido
                    </p>

                    <ul class="plan-features list-unstyled mb-3 flex-grow-1">
                        <li><i class="bi bi-check2"></i>Agenda y citas</li>
                        <li><i class="bi bi-check2"></i>Recordatorios de control</li>
                        <li><i class="bi bi-check2"></i>Historias clínicas</li>
                        <li><i class="bi bi-check2"></i>Terapia visual</li>
                        <li><i class="bi bi-check2"></i>Catálogo y tienda en línea</li>
                    </ul>

                    <a href="#que-incluye" class="plan-more mb-4">+ 4 funcionalidades más</a>

                    <a href="{{ route('register') }}"
                       class="btn btn-plan-outline rounded-pill w-100">
                        Elegir plan
                    </a>

                </div>

            </div>

            <!-- ==========================================
                 PLAN 12 MESES
            =========================================== -->
            <div class="col-lg-3 col-md-6">

                <div class="card plan-card border shadow-sm h-100 p-4">

                    <h3 class="h5 fw-bold plan-name mb-1">
                        12 meses
                    </h3>

                    <div class="plan-price mb-1">
                        <span class="plan-price-amount">$599.999</span>
                        <span class="plan-price-period">/ mes</span>
                    </div>

                    <p class="plan-billing mb-4">Facturado cada 12 meses</p>

                    <p class="plan-includes-title mb-2">
                        <i class="bi bi-stars"></i> Todo Incluido
                    </p>

                    <ul class="plan-features list-unstyled mb-3 flex-grow-1">
                        <li><i class="bi bi-check2"></i>Agenda y citas</li>
                        <li><i class="bi bi-check2"></i>Recordatorios de control</li>
                        <li><i class="bi bi-check2"></i>Historias clínicas</li>
                        <li><i class="bi bi-check2"></i>Terapia visual</li>
                        <li><i class="bi bi-check2"></i>Catálogo y tienda en línea</li>
                    </ul>

                    <a href="#que-incluye" class="plan-more mb-4">+ 4 funcionalidades más</a>

                    <a href="{{ route('register') }}"
                       class="btn btn-plan-outline rounded-pill w-100">
                        Elegir plan
                    </a>

                </div>

            </div>

        </div>

        <!-- ==========================================
             ¿QUÉ INCLUYE CADA PLAN?
        =========================================== -->
        <div id="que-incluye" class="plan-includes mt-5 pt-4">

            <div class="text-center mb-4">
                <span class="plan-eyebrow d-block mb-2">Funcionalidades</span>
                <h2 class="h3 fw-bold plan-name mb-2">¿Qué incluye cada plan?</h2>
                <p class="text-muted mx-auto plan-includes-lead">
                    Todos los planes incluyen el sistema completo. Solo cambia la duración
                    y la forma de facturación.
                </p>
            </div>

            <div class="row g-4">
                @foreach ($modulos as $m)
                    <div class="col-lg-4 col-md-6">
                        <div class="plan-feature-card h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="plan-feature-icon"><i class="bi {{ $m['icon'] }}"></i></span>
                                <div>
                                    <h3 class="h6 fw-bold mb-0 plan-name">{{ $m['titulo'] }}</h3>
                                    <p class="small text-muted mb-0">{{ $m['resumen'] }}</p>
                                </div>
                            </div>
                            <ul class="plan-features list-unstyled mb-0">
                                @foreach ($m['detalle'] as $d)
                                    <li><i class="bi bi-check2"></i>{{ $d }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

    </div>

</section>