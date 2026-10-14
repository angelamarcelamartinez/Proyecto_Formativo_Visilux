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
                    ['route' => 'superadmin.dashboard', 'match' => 'superadmin.dashboard', 'icon' => 'grid', 'label' => 'Panel general', 'badge' => $solicitudesPendientes ?? 0],
                    ['route' => 'superadmin.empresas.index', 'match' => 'superadmin.empresas.*', 'icon' => 'building', 'label' => 'Ópticas'],
                    ['route' => 'superadmin.licencias.index', 'match' => 'superadmin.licencias.*', 'icon' => 'receipt', 'label' => 'Licencias'],
                    ['route' => 'superadmin.reportes.index', 'match' => 'superadmin.reportes.*', 'icon' => 'file-text', 'label' => 'Reportes'],
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
    <div class="flex-1 min-w-0 lg:ml-72 flex flex-col min-h-screen">

        {{-- Topbar --}}
        <header class="h-20 sticky top-0 z-20 bg-cream/90 backdrop-blur border-b border-olive-100 flex items-center gap-4 px-4 sm:px-8">
            <button class="lg:hidden text-ink/70" @click="mobileNav = !mobileNav">
                @include('partials.icon', ['name' => 'grid', 'class' => 'w-6 h-6'])
            </button>

            <div class="hidden sm:flex items-center gap-1.5 text-sm font-medium text-olive-600">
                @yield('breadcrumb', 'Panel general')
            </div>

            {{-- Notificaciones: administradores bloqueados --}}
            <div class="relative ml-auto" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" title="Administradores bloqueados"
                        class="relative w-10 h-10 rounded-full border border-olive-100 bg-white flex items-center justify-center text-ink/70 hover:text-ink">
                    @include('partials.icon', ['name' => 'lock', 'class' => 'w-5 h-5'])
                    @if (($adminsBloqueados ?? collect())->count() > 0)
                        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">{{ $adminsBloqueados->count() }}</span>
                    @endif
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white border border-olive-100 rounded-xl shadow-lg overflow-hidden z-40">
                    <div class="px-4 py-3 border-b border-olive-100 text-sm font-semibold">Administradores bloqueados</div>
                    <div class="max-h-96 overflow-y-auto divide-y divide-olive-100">
                        @forelse (($adminsBloqueados ?? collect()) as $adm)
                            <div class="px-4 py-3 text-sm">
                                <p class="font-semibold leading-tight">{{ trim($adm->nombres . ' ' . $adm->apellido) }}</p>
                                <p class="text-xs text-muted">{{ $adm->empresa->nombre ?? 'Sin óptica' }} · {{ $adm->email }}</p>
                                <p class="text-xs text-red-700 mt-1">
                                    {{ $adm->por_intentos ? 'Bloqueado por 4 intentos fallidos' : 'Cuenta inactiva' }}
                                    @if ($adm->bloqueado_desde) · {{ $adm->bloqueado_desde->diffForHumans() }} @endif
                                </p>
                                <form method="POST" action="{{ route('superadmin.empresas.usuario.estado', [$adm->nit_empresa, $adm->documento]) }}" class="mt-2">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-ink text-cream text-xs font-semibold">Desbloquear</button>
                                </form>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-sm text-muted text-center">No hay administradores bloqueados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

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

        <main class="flex-1 min-w-0 px-4 sm:px-8 py-6">
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
