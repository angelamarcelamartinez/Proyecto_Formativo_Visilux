@extends('layouts.superadmin')

@section('title', 'Reportes')
@section('breadcrumb', 'Reportes')

@section('content')

    @php
        $dinero = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        $campo = 'bg-creamdark/40 border border-olive-100 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
        $botonDescarga = 'inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg border border-olive-100 bg-white hover:bg-olive-50 text-olive-700 transition';

        // Cada filtro conserva lo que el superadmin eligió en las otras secciones
        $conservar = fn (array $propios) => collect(request()->except(array_merge($propios, ['page', 'act'])))
            ->filter(fn ($v) => is_scalar($v) && $v !== '' && $v !== null);

        $filtrosEstado = [
            'todas' => 'Todas', 'vigente' => 'Vigentes', 'por_vencer' => 'Por vencer',
            'vencida' => 'Vencidas', 'sin_licencia' => 'Sin licencia',
        ];
    @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Reportes</h1>
        <p class="text-sm text-muted">Ingresos, ópticas, actividad y las ópticas que necesitan un aviso. Los reportes de ingresos y de ópticas se descargan en Excel y PDF.</p>
    </div>

    {{-- Atajos --}}
    <div class="flex items-center gap-1 bg-creamdark/70 rounded-full p-1 overflow-x-auto mb-8 w-fit max-w-full">
        @foreach (['atencion' => 'Requieren atención', 'ingresos' => 'Ingresos por licencias', 'reporte-opticas' => 'Ópticas', 'actividad' => 'Registro de actividad'] as $id => $label)
            <a href="#{{ $id }}" class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium text-ink/70 hover:text-ink hover:bg-white transition whitespace-nowrap">{{ $label }}</a>
        @endforeach
    </div>

    {{-- ============ Requieren atención ============ --}}
    <div id="atencion" class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden mb-8 scroll-mt-24">
        <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3 flex-wrap">
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                @include('partials.icon', ['name' => 'clock', 'class' => 'w-4.5 h-4.5'])
            </div>
            <div class="flex-1 min-w-[12rem]">
                <h3 class="font-serif text-lg leading-tight flex items-center gap-2">
                    Requieren atención
                    <span class="text-[11px] font-sans font-semibold bg-creamdark text-olive-700 rounded-full px-2 py-0.5 tabular-nums">{{ $alertas->count() }}</span>
                </h3>
                <p class="text-xs text-muted">Por vencer, vencidas o sin plan</p>
            </div>
            <form method="POST" action="{{ route('superadmin.avisos') }}">
                @csrf
                <button type="submit" title="Envía el correo de vencimiento a las ópticas a las que les toca hoy"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-cream bg-olive-600 hover:bg-olive-700 rounded-xl px-4 py-2.5 shadow-card transition">
                    @include('partials.icon', ['name' => 'mail', 'class' => 'w-4 h-4'])
                    Enviar avisos
                </button>
            </form>
        </div>

        @if ($alertas->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-muted">Todas las ópticas están al día.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Óptica</th>
                            <th class="py-3 pr-4 font-semibold">Correo</th>
                            <th class="py-3 pr-4 font-semibold">Plan</th>
                            <th class="py-3 pr-4 font-semibold">Vence</th>
                            <th class="py-3 pr-4 font-semibold">Días</th>
                            <th class="py-3 pr-6 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($alertas as $o)
                            <tr class="hover:bg-cream/70 transition">
                                <td class="py-3 pl-6 pr-4 whitespace-nowrap">
                                    <a href="{{ route('superadmin.empresas.show', $o->nit) }}" class="font-medium hover:text-olive-700">{{ $o->empresa }}</a>
                                    <p class="text-[11px] text-muted">NIT {{ $o->nit }}</p>
                                </td>
                                <td class="py-3 pr-4 text-ink/70 whitespace-nowrap">{{ $o->email }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap">{{ $o->plan ?? 'Sin plan' }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/70 tabular-nums">{{ $o->fecha_fin ? \Illuminate\Support\Carbon::parse($o->fecha_fin)->format('d/m/Y') : '—' }}</td>
                                <td class="py-3 pr-4">@include('partials.estado-licencia', ['dias' => $o->dias_restantes !== null ? (int) $o->dias_restantes : null, 'solo' => 'dias'])</td>
                                <td class="py-3 pr-6">@include('partials.estado-licencia', ['estado' => $o->estado_licencia, 'solo' => 'estado'])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ============ Ingresos por licencias ============ --}}
    <div id="ingresos" class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden mb-8 scroll-mt-24">
        <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3 flex-wrap">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                @include('partials.icon', ['name' => 'receipt', 'class' => 'w-4.5 h-4.5'])
            </div>
            <div class="flex-1 min-w-[12rem]">
                <h3 class="font-serif text-lg leading-tight">Ingresos por licencias</h3>
                <p class="text-xs text-muted">Pagos aprobados, mes a mes</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('superadmin.reportes.descargar', ['reporte' => 'ingresos', 'formato' => 'excel', 'desde' => $desde, 'hasta' => $hasta]) }}" class="{{ $botonDescarga }}">
                    @include('partials.icon', ['name' => 'download', 'class' => 'w-3.5 h-3.5'])
                    Excel
                </a>
                <a href="{{ route('superadmin.reportes.descargar', ['reporte' => 'ingresos', 'formato' => 'pdf', 'desde' => $desde, 'hasta' => $hasta]) }}" class="{{ $botonDescarga }}">
                    @include('partials.icon', ['name' => 'download', 'class' => 'w-3.5 h-3.5'])
                    PDF
                </a>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <form method="GET" action="{{ route('superadmin.reportes.index') }}#ingresos" class="flex flex-wrap items-end gap-3 mb-6">
                @foreach ($conservar(['desde', 'hasta']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label class="text-xs text-muted">Desde
                    <input type="month" name="desde" value="{{ $desde }}" class="{{ $campo }} block mt-1">
                </label>
                <label class="text-xs text-muted">Hasta
                    <input type="month" name="hasta" value="{{ $hasta }}" class="{{ $campo }} block mt-1">
                </label>
                <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2 rounded-lg">Ver</button>
            </form>

            <div class="grid grid-cols-2 gap-4 mb-6 max-w-xl">
                <div class="rounded-xl bg-creamdark/60 border border-olive-100 px-4 py-3">
                    <p class="text-xs text-muted">Total del periodo</p>
                    <p class="font-serif text-2xl tabular-nums mt-1">{{ $dinero($totalIngresos) }}</p>
                </div>
                <div class="rounded-xl bg-creamdark/60 border border-olive-100 px-4 py-3">
                    <p class="text-xs text-muted">Pagos aprobados</p>
                    <p class="font-serif text-2xl tabular-nums mt-1">{{ $cantidadPagos }}</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6 items-start">
                <div class="h-64"><canvas id="graficaIngresos"></canvas></div>

                <div class="overflow-x-auto rounded-xl border border-olive-100">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                                <th class="py-2.5 pl-4 pr-3 font-semibold">Mes</th>
                                <th class="py-2.5 pr-3 font-semibold text-right">Pagos</th>
                                <th class="py-2.5 pr-4 font-semibold text-right">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-olive-100">
                            @foreach ($meses as $m)
                                <tr>
                                    <td class="py-2.5 pl-4 pr-3">{{ $m['etiqueta'] }}</td>
                                    <td class="py-2.5 pr-3 text-right tabular-nums">{{ $m['pagos'] }}</td>
                                    <td class="py-2.5 pr-4 text-right tabular-nums font-medium">{{ $dinero($m['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-cream font-semibold">
                                <td class="py-2.5 pl-4 pr-3">Total</td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">{{ $cantidadPagos }}</td>
                                <td class="py-2.5 pr-4 text-right tabular-nums">{{ $dinero($totalIngresos) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <p class="text-[11px] text-muted mt-4">El Excel y el PDF traen el detalle de cada pago (óptica, plan, periodo, referencia y valor).</p>
        </div>
    </div>

    {{-- ============ Reporte de ópticas ============ --}}
    <div id="reporte-opticas" class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden mb-8 scroll-mt-24">
        <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3 flex-wrap">
            <div class="w-9 h-9 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center">
                @include('partials.icon', ['name' => 'building', 'class' => 'w-4.5 h-4.5'])
            </div>
            <div class="flex-1 min-w-[12rem]">
                <h3 class="font-serif text-lg leading-tight flex items-center gap-2">
                    Reporte de ópticas
                    <span class="text-[11px] font-sans font-semibold bg-creamdark text-olive-700 rounded-full px-2 py-0.5 tabular-nums">{{ $opticas->count() }}</span>
                </h3>
                <p class="text-xs text-muted">Plan, vencimiento y estado de cada óptica</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('superadmin.reportes.descargar', array_filter(['reporte' => 'opticas', 'formato' => 'excel', 'estado' => $estado !== 'todas' ? $estado : null])) }}" class="{{ $botonDescarga }}">
                    @include('partials.icon', ['name' => 'download', 'class' => 'w-3.5 h-3.5'])
                    Excel
                </a>
                <a href="{{ route('superadmin.reportes.descargar', array_filter(['reporte' => 'opticas', 'formato' => 'pdf', 'estado' => $estado !== 'todas' ? $estado : null])) }}" class="{{ $botonDescarga }}">
                    @include('partials.icon', ['name' => 'download', 'class' => 'w-3.5 h-3.5'])
                    PDF
                </a>
            </div>
        </div>

        <div class="px-5 sm:px-6 py-4 border-b border-olive-100">
            <div class="flex items-center gap-1 bg-creamdark/70 rounded-full p-1 overflow-x-auto w-fit max-w-full">
                @foreach ($filtrosEstado as $key => $label)
                    @php $n = $key === 'todas' ? $conteos->sum() : ($conteos[$key] ?? 0); @endphp
                    <a href="{{ route('superadmin.reportes.index', $conservar(['estado'])->merge($key !== 'todas' ? ['estado' => $key] : [])->all()) }}#reporte-opticas"
                       class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium transition whitespace-nowrap
                              {{ $estado === $key ? 'bg-white text-olive-700 shadow-sm ring-1 ring-olive-100' : 'text-ink/60 hover:text-ink' }}">
                        {{ $label }} <span class="tabular-nums text-muted">{{ $n }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if ($opticas->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-muted">No hay ópticas con este filtro.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Óptica</th>
                            <th class="py-3 pr-4 font-semibold">Plan</th>
                            <th class="py-3 pr-4 font-semibold">Inicio</th>
                            <th class="py-3 pr-4 font-semibold">Vence</th>
                            <th class="py-3 pr-4 font-semibold">Días</th>
                            <th class="py-3 pr-4 font-semibold">Usuarios</th>
                            <th class="py-3 pr-6 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($opticas as $o)
                            <tr class="hover:bg-cream/70 transition">
                                <td class="py-3 pl-6 pr-4 whitespace-nowrap">
                                    <a href="{{ route('superadmin.empresas.show', $o->nit) }}" class="font-medium hover:text-olive-700">{{ $o->empresa }}</a>
                                    <p class="text-[11px] text-muted">NIT {{ $o->nit }} · {{ $o->email }}</p>
                                </td>
                                <td class="py-3 pr-4 whitespace-nowrap">{{ $o->plan ?? 'Sin plan' }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/70 tabular-nums">{{ $o->fecha_inicio ? \Illuminate\Support\Carbon::parse($o->fecha_inicio)->format('d/m/Y') : '—' }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/70 tabular-nums">{{ $o->fecha_fin ? \Illuminate\Support\Carbon::parse($o->fecha_fin)->format('d/m/Y') : '—' }}</td>
                                <td class="py-3 pr-4">@include('partials.estado-licencia', ['dias' => $o->dias_restantes !== null ? (int) $o->dias_restantes : null, 'solo' => 'dias'])</td>
                                <td class="py-3 pr-4 tabular-nums text-ink/80">{{ $o->usuarios_registrados }}</td>
                                <td class="py-3 pr-6">@include('partials.estado-licencia', ['estado' => $o->estado_licencia, 'solo' => 'estado'])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ============ Registro de actividad ============ --}}
    <div id="actividad" class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden scroll-mt-24">
        <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3 flex-wrap">
            <div class="w-9 h-9 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center">
                @include('partials.icon', ['name' => 'file-text', 'class' => 'w-4.5 h-4.5'])
            </div>
            <div class="flex-1 min-w-[12rem]">
                <h3 class="font-serif text-lg leading-tight">Registro de actividad</h3>
                <p class="text-xs text-muted">Lo que ha pasado en todas las ópticas</p>
            </div>
            <form method="GET" action="{{ route('superadmin.reportes.index') }}#actividad" class="flex flex-wrap items-center gap-2">
                @foreach ($conservar(['nit', 'accion']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="nit" class="{{ $campo }}" onchange="this.form.submit()">
                    <option value="">Todas las ópticas</option>
                    @foreach ($listaOpticas as $e)
                        <option value="{{ $e->nit }}" @selected($nitActividad === $e->nit)>{{ $e->nombre }}</option>
                    @endforeach
                </select>
                <select name="accion" class="{{ $campo }}" onchange="this.form.submit()">
                    <option value="">Todas las acciones</option>
                    @foreach ($acciones as $a)
                        <option value="{{ $a }}" @selected($accion === $a)>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
                @if ($nitActividad !== '' || $accion !== '')
                    <a href="{{ route('superadmin.reportes.index', $conservar(['nit', 'accion'])->all()) }}#actividad" class="text-xs font-semibold text-olive-600 hover:text-olive-800">Quitar filtros</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                        <th class="py-3 pl-6 pr-4 font-semibold">Fecha</th>
                        <th class="py-3 pr-4 font-semibold">Óptica</th>
                        <th class="py-3 pr-4 font-semibold">Acción</th>
                        <th class="py-3 pr-4 font-semibold">Detalle</th>
                        <th class="py-3 pr-6 font-semibold">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-olive-100">
                    @forelse ($actividad as $a)
                        <tr class="hover:bg-cream/70 transition">
                            <td class="py-3 pl-6 pr-4 whitespace-nowrap text-ink/70 tabular-nums">{{ $a->fecha?->format('d/m/Y H:i') }}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">{{ $a->empresa->nombre ?? 'VisiOptica' }}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-olive-700 bg-olive-50 rounded px-2 py-0.5">{{ $a->accion }}</span>
                            </td>
                            <td class="py-3 pr-4 text-ink/80">{{ $a->descripcion }}</td>
                            <td class="py-3 pr-6 whitespace-nowrap text-ink/70">{{ $a->usuario->nombres ?? 'Sistema' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-muted">Sin actividad registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($actividad->hasPages())
            <div class="px-6 py-4 border-t border-olive-100 flex items-center justify-between text-sm">
                <span class="text-muted">Página {{ $actividad->currentPage() }} de {{ $actividad->lastPage() }}</span>
                <div class="flex gap-2">
                    @if ($actividad->previousPageUrl())
                        <a href="{{ $actividad->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-olive-100 hover:bg-olive-50">Anterior</a>
                    @endif
                    @if ($actividad->nextPageUrl())
                        <a href="{{ $actividad->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-olive-100 hover:bg-olive-50">Siguiente</a>
                    @endif
                </div>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('graficaIngresos');
        if (!el || typeof Chart === 'undefined') return;

        new Chart(el, {
            type: 'bar',
            data: {
                labels: @json($grafica['labels']),
                datasets: [{
                    data: @json($grafica['data']),
                    backgroundColor: '#93762E',
                    borderRadius: 8,
                    maxBarThickness: 48,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (c) => '$' + Number(c.raw).toLocaleString('es-CO') } },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { callback: (v) => '$' + Number(v).toLocaleString('es-CO') } },
                },
            },
        });
    });
</script>
@endpush
