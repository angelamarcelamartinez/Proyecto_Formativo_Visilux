<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $yo = auth()->user();
        $esOptometra = (int) $yo->id_rol === (int) config('visioptica.rol_optometra');
        $nombreOptica = $yo->empresa->nombre ?? 'Óptica Visilux';

        // Mensajes de chat sin leer (insignia del menú).
        $chatSinLeer = \App\Support\ChatArchivo::totalSinLeer((string) $yo->nit_empresa, (string) $yo->documento);

        // Menú según el rol: [ruta, texto, ícono, patrón para marcarlo como activo, insignia]
        if ($esOptometra) {
            $menu = [
                ['optometra.dashboard', 'Mi día', 'grid', 'optometra.dashboard', null],
                ['optometra.agenda', 'Mi agenda', 'calendar', 'optometra.agenda', null],
                ['optometra.pacientes.index', 'Mis pacientes', 'users', 'optometra.pacientes.*', null],
                ['optometra.terapias.index', 'Terapias', 'activity', 'optometra.terapias.*', null],
                ['optometra.simulador', 'Simulador visual', 'eye', 'optometra.simulador*', null],
                ['optometra.horario', 'Mi horario', 'clock', 'optometra.horario', null],
                ['optometra.perfil', 'Mi perfil', 'user', 'optometra.perfil*', null],
                ['chat.index', 'Chat con recepción', 'mail', 'chat.*', $chatSinLeer],
            ];
            $panelNombre = 'Panel del optómetra';
        } else {
            $pendientesContacto = \Illuminate\Support\Facades\Schema::hasColumn('mensajes_contacto', 'estado_contactenos')
                ? \Illuminate\Support\Facades\DB::table('mensajes_contacto')->whereRaw("SUBSTR(REPLACE(REPLACE(REPLACE(CAST(nit_empresa AS CHAR), '-', ''), '.', ''), ' ', ''), 1, 9) = ?", [substr(preg_replace('/\\D/', '', (string) $yo->nit_empresa), 0, 9)])->where('estado_contactenos', 'Por contactar')->count()
                : 0;
            $menu = [
                ['recepcion.inicio', 'Inicio', 'grid', 'recepcion.inicio', null],
                ['recepcion.citas', 'Citas', 'calendar', 'recepcion.citas', null],
                ['recepcion.cita.nueva', 'Nueva cita', 'plus', 'recepcion.cita.nueva', null],
                ['recepcion.pacientes', 'Pacientes', 'users', 'recepcion.paciente*', null],
                ['recepcion.contacto', 'Por contactar', 'mail', 'recepcion.contacto*', $pendientesContacto],
                ['chat.index', 'Chat con optómetra', 'mail', 'chat.*', $chatSinLeer],
            ];
            $panelNombre = 'Panel de recepción';
        }
        // Solo enlaces cuyas rutas existen (si falta el panel del optómetra, no se rompe el menú).
        $menu = array_values(array_filter($menu, fn ($item) => \Illuminate\Support\Facades\Route::has($item[0])));
    @endphp
    <title>@yield('title', $panelNombre) · {{ $nombreOptica }}</title>
    @include('includes.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/panel.css') }}?v={{ @filemtime(public_path('assets/css/panel.css')) ?: '20261014' }}">
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
            <div class="w-9 h-9 rounded-full bg-olive-600 text-cream flex items-center justify-center font-serif-heading text-sm">OV</div>
            <div>
                <p class="font-serif-heading text-lg leading-tight">{{ $nombreOptica }}</p>
                <p class="text-[11px] uppercase tracking-wider text-muted">{{ $panelNombre }}</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @foreach ($menu as [$ruta, $texto, $icono, $patron, $insignia])
                <a href="{{ route($ruta) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs($patron) ? 'bg-olive-600 text-cream shadow-card' : 'text-ink/80 hover:bg-olive-100' }}">
                    @include('partials.icon', ['name' => $icono, 'class' => 'w-5 h-5 shrink-0'])
                    <span class="flex-1">{{ $texto }}</span>
                    @if ($insignia)
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 bg-amber-100 text-amber-700">{{ $insignia }}</span>
                    @endif
                </a>
            @endforeach
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

    <div x-show="mobileNav" @click="mobileNav = false" x-cloak class="fixed inset-0 bg-ink/30 z-30 lg:hidden"></div>

    <div class="flex-1 lg:ml-72 flex flex-col min-h-screen">

        <header class="h-20 sticky top-0 z-20 bg-cream/90 backdrop-blur border-b border-olive-100 flex items-center gap-4 px-4 sm:px-8">
            <button class="lg:hidden text-ink/70" @click="mobileNav = !mobileNav">
                @include('partials.icon', ['name' => 'grid', 'class' => 'w-6 h-6'])
            </button>

            <div class="hidden sm:flex items-center gap-1.5 text-sm font-medium text-olive-600">
                @yield('breadcrumb', $panelNombre)
            </div>

            <div class="flex items-center gap-3 pl-2 ml-auto">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold leading-tight">{{ $yo->nombres }} {{ $yo->apellido }}</p>
                    <p class="text-[11px] text-muted leading-tight">{{ $yo->rol->nombre_rol ?? '' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-olive-600 text-cream flex items-center justify-center text-sm font-semibold">
                    {{ \Illuminate\Support\Str::of($yo->nombres)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-8 py-6">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->has('general'))
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">{{ $errors->first('general') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
