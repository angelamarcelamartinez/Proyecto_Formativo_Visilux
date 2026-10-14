@extends('layouts.superadmin')

@section('title', 'Licencias')
@section('breadcrumb', 'Licencias')

@section('content')

    @php
        $dinero = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        $filtrosLic = ['todas' => 'Todas', 'activa' => 'Activas', 'vencida' => 'Vencidas', 'cancelada' => 'Canceladas'];
        $botonDescarga = 'inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg border border-olive-100 bg-white hover:bg-olive-50 text-olive-700 transition';
    @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Licencias</h1>
        <p class="text-sm text-muted">Las ópticas que requieren atención y el historial de licencias de todas las ópticas. Los pagos por aprobar están en el Panel general.</p>
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

    {{-- ============ Historial de licencias ============ --}}
    <div id="historial" class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden scroll-mt-24">
        <div class="px-5 sm:px-6 py-4 flex items-center justify-between flex-wrap gap-3 border-b border-olive-100">
            <div>
                <h3 class="font-serif text-lg">Historial de licencias</h3>
                <p class="text-xs text-muted">Pruebas, compras y renovaciones de todas las ópticas</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-1 bg-creamdark/70 rounded-full p-1 overflow-x-auto">
                    @foreach ($filtrosLic as $key => $label)
                        <a href="{{ route('superadmin.licencias.index', array_filter(['lic' => $key !== 'todas' ? $key : null])) }}#historial"
                           class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium transition whitespace-nowrap
                                  {{ $estadoLic === $key ? 'bg-white text-olive-700 shadow-sm ring-1 ring-olive-100' : 'text-ink/60 hover:text-ink' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                {{-- Se descarga lo que muestra el filtro elegido --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('superadmin.reportes.descargar', array_filter(['reporte' => 'licencias', 'formato' => 'excel', 'lic' => $estadoLic !== 'todas' ? $estadoLic : null])) }}" class="{{ $botonDescarga }}">
                        @include('partials.icon', ['name' => 'download', 'class' => 'w-3.5 h-3.5'])
                        Excel
                    </a>
                    <a href="{{ route('superadmin.reportes.descargar', array_filter(['reporte' => 'licencias', 'formato' => 'pdf', 'lic' => $estadoLic !== 'todas' ? $estadoLic : null])) }}" class="{{ $botonDescarga }}">
                        @include('partials.icon', ['name' => 'download', 'class' => 'w-3.5 h-3.5'])
                        PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                        <th class="py-3 pl-6 pr-4 font-semibold">Óptica</th>
                        <th class="py-3 pr-4 font-semibold">Plan</th>
                        <th class="py-3 pr-4 font-semibold">Periodo</th>
                        <th class="py-3 pr-4 font-semibold">Días</th>
                        <th class="py-3 pr-4 font-semibold">Valor</th>
                        <th class="py-3 pr-4 font-semibold">Referencia</th>
                        <th class="py-3 pr-6 font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-olive-100">
                    @forelse ($historial as $lic)
                        <tr class="hover:bg-cream/70 transition">
                            <td class="py-3 pl-6 pr-4 whitespace-nowrap">
                                <a href="{{ route('superadmin.empresas.show', $lic->nit_empresa) }}" class="font-medium hover:text-olive-700">
                                    {{ $lic->empresa->nombre ?? $lic->nit_empresa }}
                                </a>
                            </td>
                            <td class="py-3 pr-4 whitespace-nowrap">{{ $lic->plan->nombre ?? '—' }}</td>
                            <td class="py-3 pr-4 whitespace-nowrap text-ink/70 tabular-nums">
                                @if ($lic->fecha_inicio)
                                    {{ $lic->fecha_inicio->format('d/m/Y') }} – {{ $lic->fecha_fin?->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                @if ($lic->estado === 'activa')
                                    @include('partials.estado-licencia', ['dias' => $lic->diasRestantes(), 'solo' => 'dias'])
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 whitespace-nowrap tabular-nums">{{ $dinero($lic->valor) }}</td>
                            <td class="py-3 pr-4 text-ink/70">{{ $lic->referencia_pago ?? '—' }}</td>
                            <td class="py-3 pr-6">@include('partials.estado-licencia', ['estado' => $lic->estado, 'solo' => 'estado'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-muted">No hay licencias con este filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($historial->hasPages())
            <div class="px-6 py-4 border-t border-olive-100 flex items-center justify-between text-sm">
                <span class="text-muted">Página {{ $historial->currentPage() }} de {{ $historial->lastPage() }}</span>
                <div class="flex gap-2">
                    @if ($historial->previousPageUrl())
                        <a href="{{ $historial->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-olive-100 hover:bg-olive-50">Anterior</a>
                    @endif
                    @if ($historial->nextPageUrl())
                        <a href="{{ $historial->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-olive-100 hover:bg-olive-50">Siguiente</a>
                    @endif
                </div>
            </div>
        @endif
    </div>

@endsection
