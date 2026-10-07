<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Título dinámico -->
    <title>{{ $pageTitle ?? config('VisiOptica') }} VisiOptica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.ico') }}">

    <!-- Fuentes y estilos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Ruta de assets de Laravel -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="{{ $sitioEmpresa?->urlPagina() ?? url('/') }}">
            <img src="{{ $sitio?->imagen('logo') ?? asset('assets/img/logo-visilux.png') }}" alt="{{ $sitioEmpresa->nombre ?? 'VisiOptica' }}" class="navbar-logo">
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home', 'optica.show') ? 'active' : '' }}" href="{{ $sitioEmpresa?->urlPagina() ?? url('/') }}#inicio" @if (request()->routeIs('home', 'optica.show')) aria-current="page" @endif>Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('productos.index') ? 'active' : '' }}" href="{{ route('productos.index') }}">Productos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('terapia-visual') ? 'active' : '' }}" href="{{ route('terapia-visual') }}">Terapia Visual</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('conocenos*') ? 'active' : '' }}" href="{{ route('conocenos') }}">Conócenos</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('citas.create') }}" class="btn btn-gradient rounded-pill px-4 {{ request()->routeIs('citas.*') ? 'is-active' : '' }}">
                    <i class="bi bi-calendar-event me-1"></i> Agenda tu Cita
                </a>

                <!-- Autenticación nativa de Laravel -->
                @auth
                    <!-- Si ha iniciado sesión: Menú desplegable con el usuario y Cerrar sesión -->
                    <div class="dropdown">
                        <a href="#" class="btn btn-outline-teal rounded-circle p-2 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false" title="{{ Auth::user()->nombres ?? 'Mi cuenta' }}">
                            <i class="bi bi-person-check-fill"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                            <li>
                                <span class="dropdown-item-text text-muted small fw-semibold">
                                    Hola, {{ Auth::user()->nombres ?? 'Usuario' }}
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <!-- Formulario POST seguro para cerrar sesión -->
                                <form action="{{ route('logout') }}" method="POST" class="d-block w-100 m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 fw-medium text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <!-- Si no ha iniciado sesión, muestra un menú desplegable con Login y Registro -->
                    <div class="dropdown">
                        <a href="#" class="btn btn-outline-teal rounded-circle p-2 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false" title="Cuenta">
                            <i class="bi bi-person-fill"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                            <li>
                                <a class="dropdown-item py-2 fw-medium" href="{{ route('login') }}">
                                    <i class="bi bi-box-arrow-in-right me-2 text-teal"></i> Iniciar sesión
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 fw-medium" href="{{ route('register') }}">
                                    <i class="bi bi-person-plus me-2 text-teal"></i> Registrarse
                                </a>
                            </li>
                        </ul>
                    </div>
                @endauth

                <a href="{{ route('carrito.index') }}" class="position-relative cart-link {{ request()->routeIs('carrito.*') ? 'active' : 'text-dark' }}" aria-label="Carrito">
                    <i class="bi bi-cart3 fs-5"></i>
                    @if ($cartCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</nav>
<br>
