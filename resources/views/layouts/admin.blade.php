<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel general') · Óptica Visilux</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        cream: '#FAF7F2',
                        creamdark: '#F1EBDD',
                        olive: {
                            50: '#F7F1E1', 100: '#EFE2C4', 300: '#C8AD6C',
                            500: '#93762E', 600: '#7E6427', 700: '#6B5522', 800: '#4A3A18',
                        },
                        ink: '#2E2A22',
                        muted: '#8A8477',
                    },
                    fontFamily: {
                        serif: ['Fraunces', 'Georgia', 'serif'],
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    boxShadow: {
                        card: '0 1px 2px rgba(46,42,34,0.06), 0 8px 24px -12px rgba(46,42,34,0.12)',
                    },
                }
            }
        }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui; }
        .font-serif-heading { font-family: 'Fraunces', Georgia, serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #E4DCC7; border-radius: 8px; }
        [x-cloak] { display: none !important; }

        /* Tus colores originales exactos */
        :root {
            --cream: #FAF7F2;
            --creamdark: #F1EBDD;
            --olive-50: #F7F1E1;
            --olive-100: #EFE2C4;
            --olive-300: #C8AD6C;
            --olive-500: #93762E;
            --olive-600: #7E6427;
            --olive-700: #6B5522;
            --olive-800: #4A3A18;
            --ink: #2E2A22;
            --muted: #8A8477;
        }

        .bg-cream { background-color: var(--cream) !important; }
        .bg-creamdark { background-color: var(--creamdark) !important; }
        .bg-olive-50 { background-color: var(--olive-50) !important; }
        .bg-olive-100 { background-color: var(--olive-100) !important; }
        .bg-olive-500 { background-color: var(--olive-500) !important; }
        .bg-olive-600 { background-color: var(--olive-600) !important; }
        
        /* Asegurar soporte absoluto para el fondo oscuro y texto claro del banner */
        .bg-ink { background-color: var(--ink) !important; }
        .text-cream { color: var(--cream) !important; }

        .text-olive-600 { color: var(--olive-600) !important; }
        .text-olive-700 { color: var(--olive-700) !important; }
        .text-ink { color: var(--ink) !important; }
        .text-muted { color: var(--muted) !important; }
        .border-olive-100 { border-color: var(--olive-100) !important; }
    </style>

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
            <div class="w-9 h-9 rounded-full bg-olive-600 text-cream flex items-center justify-center font-serif-heading text-sm">OV</div>
            <div>
                <p class="font-serif-heading text-lg leading-tight">Óptica Visilux</p>
                <p class="text-[11px] uppercase tracking-wider text-muted">Panel de administración</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.dashboard') ? 'bg-olive-600 text-cream shadow-card' : 'text-ink/80 hover:bg-olive-100' }}">
                @include('partials.icon', ['name' => 'grid', 'class' => 'w-5 h-5 shrink-0'])
                Panel general
            </a>

            <p class="px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-muted">Tablas del sistema</p>

            @foreach (config('admin_tables.groups') as $groupKey => $group)
                @php
                    $tablesInGroup = collect(config('admin_tables.tables'))->filter(fn ($t) => $t['group'] === $groupKey);
                    $isActiveGroup = (request()->routeIs('admin.crud.*') && $tablesInGroup->has(request()->route('table')))
                        || ($groupKey === 'agenda' && request()->routeIs('admin.control.*'));
                @endphp
                <div x-data="{ open: {{ $isActiveGroup ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                                   {{ $isActiveGroup ? 'bg-olive-100 text-olive-700' : 'text-ink/80 hover:bg-olive-100' }}">
                        @include('partials.icon', ['name' => $group['icon'], 'class' => 'w-5 h-5 shrink-0'])
                        <span class="flex-1 text-left">{{ $group['label'] }}</span>
                        <span class="text-[11px] text-muted">{{ $tablesInGroup->count() }}</span>
                        <span :class="open && 'rotate-180'" class="transition-transform">
                            @include('partials.icon', ['name' => 'chevron-down', 'class' => 'w-4 h-4'])
                        </span>
                    </button>
                    <div x-show="open" x-collapse class="pl-11 pr-2 py-1 space-y-0.5">
                        @foreach ($tablesInGroup as $tableName => $tableCfg)
                            <a href="{{ route('admin.crud.index', $tableName) }}"
                               class="block px-2 py-1.5 rounded-md text-[13px] transition
                                      {{ request()->route('table') === $tableName ? 'text-olive-700 font-semibold bg-olive-50' : 'text-ink/70 hover:text-olive-700 hover:bg-olive-50' }}">
                                {{ $tableCfg['label_plural'] }}
                            </a>
                        @endforeach

                        @if ($groupKey === 'agenda')
                            <a href="{{ route('admin.control.index') }}"
                               class="flex items-center gap-1.5 px-2 py-1.5 rounded-md text-[13px] transition
                                      {{ request()->routeIs('admin.control.*') ? 'text-olive-700 font-semibold bg-olive-50' : 'text-ink/70 hover:text-olive-700 hover:bg-olive-50' }}">
                                @include('partials.icon', ['name' => 'mail', 'class' => 'w-3.5 h-3.5'])
                                Recordatorios de control
                            </a>
                        @endif
                    </div>
                </div>
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

            {{-- Buscador rápido: salta a cualquiera de las 48 tablas del sistema --}}
            <div class="flex-1 max-w-md ml-auto relative"
                 x-data="{
                    open: false,
                    q: '',
                    tables: {{ collect(config('admin_tables.tables'))->map(fn ($t, $k) => ['key' => $k, 'label' => $t['label_plural'], 'group' => $t['group_label']])->values()->toJson() }},
                    get results() {
                        if (this.q.length < 1) return [];
                        const needle = this.q.toLowerCase();
                        return this.tables.filter(t => t.label.toLowerCase().includes(needle)).slice(0, 8);
                    },
                 }" @keydown.escape="open=false">
                <span class="absolute inset-y-0 left-3 flex items-center text-muted pointer-events-none">
                    @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                </span>
                <input type="text" x-model="q" @focus="open = true" @click.outside="open = false"
                       placeholder="Buscar cualquier tabla del sistema..."
                       class="w-full bg-creamdark/70 border border-olive-100 rounded-full py-2.5 pl-9 pr-4 text-sm placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-olive-300">
                <div x-show="open && results.length" x-cloak
                     class="absolute mt-2 w-full bg-white border border-olive-100 rounded-xl shadow-card overflow-hidden z-30">
                    <template x-for="r in results" :key="r.key">
                        <a :href="'{{ url('admin') }}/' + r.key"
                           class="flex items-center justify-between px-4 py-2.5 text-sm hover:bg-olive-50">
                            <span x-text="r.label"></span>
                            <span class="text-[11px] text-muted" x-text="r.group"></span>
                        </a>
                    </template>
                </div>
            </div>

            <div class="flex items-center gap-3 pl-2">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold leading-tight">{{ auth()->user()->nombres ?? 'Administrador' }}</p>
                    <p class="text-[11px] text-muted leading-tight">{{ auth()->user()->rol->nombre_rol ?? 'Administradora' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-olive-600 text-cream flex items-center justify-center text-sm font-semibold">
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
