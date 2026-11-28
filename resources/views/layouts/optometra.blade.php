<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $yo = auth()->user();
        $miOptica = $yo?->empresa;
        $perfilOpt = \App\Support\Optometria::perfil();
        $hoyPendientes = \App\Support\Optometria::misCitas()
            ->whereDate('asignacion_cita.fecha_cita', now()->toDateString())
            ->whereNull('hc.id_historia_clinica')
            ->where('asignacion_cita.id_estado', '!=', \App\Support\Optometria::estado('Cancelado') ?? 0)
            ->count();
        $menu = [
            ['ruta' => 'optometra.dashboard', 'activo' => 'optometra.dashboard', 'icono' => 'grid', 'texto' => 'Mi día', 'badge' => $hoyPendientes],
            ['ruta' => 'optometra.agenda', 'activo' => 'optometra.agenda', 'icono' => 'calendar', 'texto' => 'Agenda'],
            ['ruta' => 'optometra.pacientes.index', 'activo' => 'optometra.pacientes.*', 'icono' => 'users', 'texto' => 'Pacientes'],
            ['ruta' => 'optometra.terapias.index', 'activo' => 'optometra.terapias.*', 'icono' => 'activity', 'texto' => 'Terapias'],
            ['ruta' => 'optometra.simulador', 'activo' => 'optometra.simulador*', 'icono' => 'eye', 'texto' => 'Simulador visual'],
            ['ruta' => 'optometra.horario', 'activo' => 'optometra.horario', 'icono' => 'clock', 'texto' => 'Mi horario'],
            ['ruta' => 'optometra.perfil', 'activo' => 'optometra.perfil*', 'icono' => 'user', 'texto' => 'Mi perfil'],
        ];
        // Chat interno con recepción (módulo de la recepcionista), con los mensajes sin leer.
        if (\Illuminate\Support\Facades\Route::has('chat.index')) {
            $chatSinLeer = class_exists(\App\Support\ChatArchivo::class)
                ? \App\Support\ChatArchivo::totalSinLeer((string) $yo->nit_empresa, (string) $yo->documento)
                : 0;
            array_splice($menu, 5, 0, [['ruta' => 'chat.index', 'activo' => 'chat.*', 'icono' => 'mail', 'texto' => 'Chat con recepción', 'badge' => $chatSinLeer]]);
        }
    @endphp
    <title>@yield('title', 'Mi día') · {{ $miOptica->nombre ?? 'Óptica' }}</title>
    @include('includes.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Mismo estilo del panel de administración + estilos propios del módulo (sin CDNs) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/panel.css') }}?v={{ @filemtime(public_path('assets/css/panel.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('assets/css/optometra.css') }}?v={{ @filemtime(public_path('assets/css/optometra.css')) ?: '1' }}">
    <script defer src="{{ asset('assets/js/alpine-collapse.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/alpine.min.js') }}"></script>
    @stack('head')
</head>
<body class="bg-cream text-ink antialiased">
<div class="min-h-screen flex" x-data="{ mobileNav: false }">

    {{-- ---------- Menú lateral ---------- --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-creamdark border-r border-olive-100 flex flex-col transition-transform -translate-x-full lg:translate-x-0"
           :class="mobileNav && '!translate-x-0'">
        <div class="h-20 flex items-center gap-3 px-6 border-b border-olive-100">
            <div class="w-9 h-9 rounded-full bg-olive-600 text-cream flex items-center justify-center">
                @include('partials.icon', ['name' => 'eye', 'class' => 'w-5 h-5'])
            </div>
            <div class="min-w-0">
                <p class="font-serif-heading text-lg leading-tight truncate">{{ $miOptica->nombre ?? 'Óptica' }}</p>
                <p class="text-[11px] uppercase tracking-wider text-muted">Consultorio de optometría</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @foreach ($menu as $item)
                @php $activo = request()->routeIs($item['activo']); @endphp
                <a href="{{ route($item['ruta']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ $activo ? 'bg-olive-600 text-cream shadow-card' : 'text-ink/80 hover:bg-olive-100' }}">
                    @include('partials.icon', ['name' => $item['icono'], 'class' => 'w-5 h-5 shrink-0'])
                    <span class="flex-1">{{ $item['texto'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 {{ $activo ? 'bg-cream text-olive-700' : 'bg-amber-100 text-amber-700' }}">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($perfilOpt && ! $perfilOpt->tarjeta_profesional)
            <a href="{{ route('optometra.perfil') }}" class="mx-3 mb-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-3 py-2.5 text-xs leading-snug">
                Registra tu <b>tarjeta profesional</b> para que salga en las fórmulas impresas.
            </a>
        @endif

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

    <div x-show="mobileNav" @click="mobileNav = false" x-cloak class="fixed inset-0 bg-ink/30 z-30 lg:hidden"></div>

    {{-- ---------- Columna principal ---------- --}}
    <div class="flex-1 lg:ml-72 flex flex-col min-h-screen min-w-0">
        <header class="h-20 sticky top-0 z-20 bg-cream/90 backdrop-blur border-b border-olive-100 flex items-center gap-4 px-4 sm:px-8">
            <button class="lg:hidden text-ink/70" @click="mobileNav = !mobileNav" aria-label="Menú">
                @include('partials.icon', ['name' => 'grid', 'class' => 'w-6 h-6'])
            </button>

            <div class="hidden sm:flex items-center gap-1.5 text-sm font-medium text-olive-600">
                @yield('breadcrumb', 'Mi día')
            </div>

            {{-- Buscar paciente por documento o nombre --}}
            <form method="GET" action="{{ route('optometra.pacientes.index') }}" class="flex-1 max-w-md ml-auto relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-muted pointer-events-none">
                    @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                </span>
                <input type="search" name="q" value="{{ request()->routeIs('optometra.pacientes.index') ? request('q') : '' }}"
                       placeholder="Buscar paciente por documento o nombre…"
                       class="w-full bg-creamdark/70 border border-olive-100 rounded-full py-2.5 pl-9 pr-4 text-sm placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-olive-300">
            </form>

            <a href="{{ route('optometra.perfil') }}" class="flex items-center gap-3 pl-2">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold leading-tight">{{ trim(($yo->nombres ?? '') . ' ' . \Illuminate\Support\Str::of($yo->apellido ?? '')->explode(' ')->first()) }}</p>
                    <p class="text-[11px] text-muted leading-tight">{{ $perfilOpt->nombre_especialidad ?? 'Optómetra' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-olive-600 text-cream flex items-center justify-center text-sm font-semibold">
                    {{ \Illuminate\Support\Str::of(($yo->nombres ?? 'O') . ' ' . ($yo->apellido ?? ''))->explode(' ')->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('') }}
                </div>
            </a>
        </header>

        <main class="flex-1 px-4 sm:px-8 py-6">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm flex items-center gap-2">
                    @include('partials.icon', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0'])
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->has('general'))
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                    {{ $errors->first('general') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
