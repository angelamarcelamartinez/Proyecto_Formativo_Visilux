@extends('layouts.admin')

@section('title', 'Panel general')
@section('breadcrumb', 'Panel general')

@section('content')

    @php
        $hora = now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = \Illuminate\Support\Str::of($usuario->nombres ?? 'Administrador')->explode(' ')->first();

        // Colores por estado de la cita. Las clases van completas (no armadas por partes)
        // para que Tailwind las detecte al compilar.
        $estadoEstilos = [
            'confirmada' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'atendida'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'completada' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'pendiente'  => 'bg-amber-50 text-amber-700 ring-amber-200',
            'agendada'   => 'bg-amber-50 text-amber-700 ring-amber-200',
            'programada' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'en proceso' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'cancelada'  => 'bg-rose-50 text-rose-700 ring-rose-200',
            'no asistió' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
        $estadoPunto = [
            'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500',
            'sky' => 'bg-sky-500', 'rose' => 'bg-rose-500', 'olive' => 'bg-olive-500',
        ];
        $estiloEstado = function ($estado) use ($estadoEstilos, $estadoPunto) {
            $clases = $estadoEstilos[mb_strtolower(trim((string) $estado))] ?? 'bg-olive-50 text-olive-700 ring-olive-100';
            preg_match('/bg-(\w+)-50/', $clases, $m);
            return [$clases, $estadoPunto[$m[1] ?? 'olive'] ?? 'bg-olive-500'];
        };

        $iniciales = fn ($n) => \Illuminate\Support\Str::of($n ?? '?')->explode(' ')->filter()
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');

        $fechaCorta = function ($f) {
            try {
                // "jue. 24 sep." → "jue 24 sep"
                return str_replace('.', '', \Illuminate\Support\Carbon::parse($f)->locale('es')->translatedFormat('D j M'));
            } catch (\Throwable $e) {
                return \Illuminate\Support\Str::substr((string) $f, 0, 10);
            }
        };
    @endphp

    {{-- Hero --}}
    <div class="relative rounded-3xl overflow-hidden mb-8 bg-ink text-cream">
        <div class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_80%_20%,#93762E,transparent_55%)]"></div>
        <div class="relative px-6 sm:px-10 py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <p class="text-[11px] uppercase tracking-[0.2em] text-olive-300">Panel general &middot; Óptica Visilux</p>
                <h1 class="font-serif text-3xl sm:text-4xl mt-3">{{ $saludo }}, {{ $nombre }}</h1>
                <p class="mt-3 text-sm text-cream/70 max-w-lg leading-relaxed">
                    Administra las {{ $totalTablas }} tablas del sistema — usuarios, catálogo, citas,
                    historias clínicas, ventas y envíos.
                </p>
            </div>
            <a href="{{ route('admin.crud.create', 'usuario') }}"
               class="inline-flex items-center justify-center gap-2 bg-olive-500 hover:bg-olive-600 transition text-cream text-sm font-medium px-5 py-3 rounded-xl shadow-lg shrink-0">
                @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                Nuevo registro
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    @php
        $stats = [
            [
                'icon' => 'calendar-check', 'value' => $citasHoy, 'label' => 'Citas agendadas hoy',
                'hint' => $citasHoy > 0 ? 'Agenda del día' : 'Sin citas por ahora',
                'tone' => 'bg-olive-100 text-olive-700', 'bar' => 'bg-olive-500',
            ],
            [
                'icon' => 'cart', 'label' => 'Ventas del día',
                'value' => $ventasHoy >= 1000000
                    ? '$' . number_format($ventasHoy / 1000000, 1, ',', '.') . 'M'
                    : '$' . number_format($ventasHoy, 0, ',', '.'),
                'hint' => 'Productos + medicamentos',
                'tone' => 'bg-emerald-50 text-emerald-700', 'bar' => 'bg-emerald-500',
            ],
            [
                'icon' => 'truck', 'value' => $pedidosEnTransito, 'label' => 'Pedidos en tránsito',
                'hint' => 'Enviados o en proceso',
                'tone' => 'bg-sky-50 text-sky-700', 'bar' => 'bg-sky-500',
            ],
            [
                'icon' => 'archive', 'value' => $stockBajo, 'label' => 'Stock bajo',
                'hint' => $stockBajo > 0 ? 'Requiere reposición' : 'Inventario al día',
                'tone' => $stockBajo > 0 ? 'bg-rose-50 text-rose-700' : 'bg-olive-100 text-olive-700',
                'bar' => $stockBajo > 0 ? 'bg-rose-500' : 'bg-olive-500',
                'alert' => $stockBajo > 0,
            ],
            [
                'icon' => 'mail', 'value' => $controlesPendientes, 'label' => 'Controles por recordar',
                'hint' => 'Próximos 30 días',
                'tone' => 'bg-amber-50 text-amber-700', 'bar' => 'bg-amber-500',
                'link' => route('admin.control.index'),
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4 sm:gap-5 mb-8">
        @foreach ($stats as $i => $stat)
            @php $esLink = isset($stat['link']); @endphp
            <{{ $esLink ? 'a' : 'div' }}
                @if ($esLink) href="{{ $stat['link'] }}" @endif
                class="group relative overflow-hidden bg-white rounded-2xl border border-olive-100 shadow-card p-5 sm:p-6 flex flex-col
                       {{ $esLink ? 'hover:-translate-y-0.5 hover:border-olive-300 hover:shadow-lg transition duration-200' : '' }}
                       {{ $loop->last ? 'col-span-2 xl:col-span-1' : '' }}">

                {{-- barra de acento superior --}}
                <span class="absolute inset-x-0 top-0 h-1 {{ $stat['bar'] }} opacity-80"></span>

                <div class="flex items-start justify-between mb-5">
                    <div class="w-11 h-11 rounded-xl {{ $stat['tone'] }} flex items-center justify-center">
                        @include('partials.icon', ['name' => $stat['icon'], 'class' => 'w-5 h-5'])
                    </div>

                    @if (! empty($stat['alert']))
                        <span class="relative flex h-2.5 w-2.5 mt-1">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-60"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                        </span>
                    @elseif ($esLink)
                        <span class="text-[11px] font-medium text-olive-600 opacity-70 group-hover:opacity-100 transition flex items-center gap-1">
                            Ver <span class="transition group-hover:translate-x-0.5">→</span>
                        </span>
                    @endif
                </div>

                <p class="font-serif text-3xl sm:text-[34px] leading-none tabular-nums text-ink">{{ $stat['value'] }}</p>
                <p class="text-sm font-medium text-ink/80 mt-2">{{ $stat['label'] }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $stat['hint'] }}</p>
            </{{ $esLink ? 'a' : 'div' }}>
        @endforeach
    </div>

    {{-- Citas, con filtro de rango --}}
    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">

        {{-- Encabezado --}}
        <div class="px-5 sm:px-6 pt-5 sm:pt-6 pb-4 flex items-center justify-between flex-wrap gap-3 border-b border-olive-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center">
                    @include('partials.icon', ['name' => 'calendar', 'class' => 'w-4.5 h-4.5'])
                </div>
                <div>
                    <h3 class="font-serif text-lg leading-tight flex items-center gap-2">
                        Citas
                        <span class="text-[11px] font-sans font-semibold bg-creamdark text-olive-700 rounded-full px-2 py-0.5 tabular-nums">
                            {{ $citasLista->count() }}
                        </span>
                    </h3>
                    <p class="text-xs text-muted">
                        {{ ['dia' => 'Hoy', 'semana' => 'Esta semana', 'mes' => 'Este mes'][$rango] }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center gap-1 bg-creamdark/70 rounded-full p-1 flex-1 sm:flex-none">
                    @foreach (['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes'] as $key => $label)
                        <a href="{{ route('admin.dashboard', ['rango' => $key]) }}"
                           class="flex-1 sm:flex-none text-center px-4 py-1.5 rounded-full text-xs font-medium transition
                                  {{ $rango === $key ? 'bg-white text-olive-700 shadow-sm ring-1 ring-olive-100' : 'text-ink/60 hover:text-ink' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('admin.crud.index', 'asignacion_cita') }}"
                   class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-olive-600 hover:text-olive-800 transition">
                    Ver todas <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>

        @if ($citasLista->isEmpty())
            {{-- Estado vacío --}}
            <div class="px-6 py-14 text-center">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-creamdark text-olive-600 flex items-center justify-center mb-4">
                    @include('partials.icon', ['name' => 'calendar', 'class' => 'w-6 h-6'])
                </div>
                <p class="font-serif text-lg">Sin citas en este rango</p>
                <p class="text-sm text-muted mt-1">Prueba con otro filtro o revisa la agenda completa.</p>
            </div>
        @else
            {{-- Móvil: tarjetas --}}
            <ul class="sm:hidden divide-y divide-olive-100">
                @foreach ($citasLista as $cita)
                    @php [$clsEstado, $punto] = $estiloEstado($cita->estado); @endphp
                    <li class="px-5 py-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-olive-50 text-olive-700 ring-1 ring-olive-100 flex items-center justify-center text-xs font-semibold shrink-0">
                            {{ $iniciales($cita->paciente) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm truncate">{{ $cita->paciente }}</p>
                            <p class="text-xs text-muted truncate">{{ $cita->optometra }}</p>
                            <p class="text-xs text-ink/70 mt-1 tabular-nums">
                                {{ $fechaCorta($cita->fecha_cita) }} · {{ \Illuminate\Support\Str::substr($cita->hora_cita, 0, 5) }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full ring-1 ring-inset {{ $clsEstado }} shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full {{ $punto }}"></span>
                            {{ $cita->estado }}
                        </span>
                    </li>
                @endforeach
            </ul>

            {{-- Escritorio: tabla --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Paciente</th>
                            <th class="py-3 pr-4 font-semibold">Optómetra</th>
                            <th class="py-3 pr-4 font-semibold">Fecha</th>
                            <th class="py-3 pr-4 font-semibold">Hora</th>
                            <th class="py-3 pr-6 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($citasLista as $cita)
                            @php [$clsEstado, $punto] = $estiloEstado($cita->estado); @endphp
                            <tr class="hover:bg-cream/70 transition">
                                <td class="py-3 pl-6 pr-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-olive-50 text-olive-700 ring-1 ring-olive-100 flex items-center justify-center text-[11px] font-semibold shrink-0">
                                            {{ $iniciales($cita->paciente) }}
                                        </div>
                                        <span class="font-medium whitespace-nowrap">{{ $cita->paciente }}</span>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 text-ink/70 whitespace-nowrap">
                                    {{ $cita->optometra }}
                                </td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/80">{{ $fechaCorta($cita->fecha_cita) }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 tabular-nums font-medium">
                                        @include('partials.icon', ['name' => 'clock', 'class' => 'w-3.5 h-3.5 text-muted'])
                                        {{ \Illuminate\Support\Str::substr($cita->hora_cita, 0, 5) }}
                                    </span>
                                </td>
                                <td class="py-3 pr-6">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full ring-1 ring-inset {{ $clsEstado }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $punto }}"></span>
                                        {{ $cita->estado }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection