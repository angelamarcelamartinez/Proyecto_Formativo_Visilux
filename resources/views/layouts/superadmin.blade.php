<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel general') · VisiOptica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Estilos y scripts locales: el panel funciona sin depender de CDNs --}}
    <link rel="stylesheet" href="{{ asset('assets/css/panel.css') }}?v={{ filemtime(public_path('assets/css/panel.css')) }}">
    <script defer src="{{ asset('assets/js/alpine-collapse.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/alpine.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/chart.umd.min.js') }}"></script>

    @stack('head')
</head>
<body class="bg-cream text-ink antialiased">
<div class="min-h-screen flex" x-data="{ mobileNav: false }">

    {{-- ---------- Sidebar ---------- --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 w-72 bg-creamdark border-r border-olive-100 flex flex-col transition-transform -translate-x-full lg:translate-x-0"
        :class="mobileNav && '!translate-x-0'"
    >
        <div class="h-20 flex items-center gap-3 px-6 border-b border-olive-100">
            <div class="w-9 h-9 rounded-full bg-ink text-cream flex items-center justify-center font-serif-heading text-sm">VO</div>
            <div>
                <p class="font-serif-heading text-lg leading-tight">VisiOptica</p>
                <p class="text-[11px] uppercase tracking-wider text-muted">Superadministrador</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @php
                $menu = [
                    ['route' => 'superadmin.dashboard', 'match' => 'superadmin.dashboard', 'icon' => 'grid', 'label' => 'Panel general'],
                    ['route' => 'superadmin.empresas.index', 'match' => 'superadmin.empresas.*', 'icon' => 'building', 'label' => 'Ópticas'],
                    ['route' => 'superadmin.licencias.index', 'match' => 'superadmin.licencias.*', 'icon' => 'shield', 'label' => 'Licencias y pagos', 'badge' => $solicitudesPendientes ?? 0],
                    ['route' => 'superadmin.planes.index', 'match' => 'superadmin.planes.*', 'icon' => 'tag', 'label' => 'Planes'],
                ];
            @endphp

            @foreach ($menu as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs($item['match']) ? 'bg-olive-600 text-cream shadow-card' : 'text-ink/80 hover:bg-olive-100' }}">
                    @include('partials.icon', ['name' => $item['icon'], 'class' => 'w-5 h-5 shrink-0'])
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 bg-amber-100 text-amber-700 tabular-nums">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach

            <p class="px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-muted">Accesos rápidos</p>

            <a href="{{ route('superadmin.empresas.create') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-ink/80 hover:bg-olive-100 transition">
                @include('partials.icon', ['name' => 'plus', 'class' => 'w-5 h-5 shrink-0'])
                Registrar óptica
            </a>
            <a href="{{ url('/') }}" target="_blank"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-ink/80 hover:bg-olive-100 transition">
                @include('partials.icon', ['name' => 'eye', 'class' => 'w-5 h-5 shrink-0'])
                Ver sitio público
            </a>
        </nav>

        <div class="p-4 border-t border-olive-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2 justify-center px-3 py-2 rounded-lg text-sm font-medium text-ink/70 hover:bg-olive-100 transition">
                    @include('partials.icon', ['name' => 'logout', 'class' => 'w-4 h-4'])
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- backdrop for mobile nav --}}
    <div x-show="mobileNav" @click="mobileNav = false" x-cloak class="fixed inset-0 bg-ink/30 z-30 lg:hidden"></div>

    {{-- ---------- Main column ---------- --}}
    <div class="flex-1 lg:ml-72 flex flex-col min-h-screen">

        {{-- Topbar --}}
        <header class="h-20 sticky top-0 z-20 bg-cream/90 backdrop-blur border-b border-olive-100 flex items-center gap-4 px-4 sm:px-8">
            <button class="lg:hidden text-ink/70" @click="mobileNav = !mobileNav">
                @include('partials.icon', ['name' => 'grid', 'class' => 'w-6 h-6'])
            </button>

            <div class="hidden sm:flex items-center gap-1.5 text-sm font-medium text-olive-600">
                @yield('breadcrumb', 'Panel general')
            </div>

            {{-- Buscador de ópticas --}}
            <form method="GET" action="{{ route('superadmin.empresas.index') }}" class="flex-1 max-w-md ml-auto relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-muted pointer-events-none">
                    @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                </span>
                <input type="text" name="q" value="{{ request()->routeIs('superadmin.empresas.index') ? request('q') : '' }}"
                       placeholder="Buscar óptica por nombre, NIT o correo..."
                       class="w-full bg-creamdark/70 border border-olive-100 rounded-full py-2.5 pl-9 pr-4 text-sm placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-olive-300">
            </form>

            <div class="flex items-center gap-3 pl-2">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold leading-tight">{{ auth()->user()->nombres ?? 'Administrador' }}</p>
                    <p class="text-[11px] text-muted leading-tight">{{ auth()->user()->rol->nombre_rol ?? 'Superadmin' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-ink text-cream flex items-center justify-center text-sm font-semibold">
                    {{ \Illuminate\Support\Str::of(auth()->user()->nombres ?? 'A')->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-8 py-6">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            {{-- Las vistas con formulario muestran los errores junto a cada campo --}}
            @hasSection('errores_en_formulario')
            @else
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
