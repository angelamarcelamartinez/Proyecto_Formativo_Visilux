@extends('layouts.superadmin')

@section('title', 'Licencias y pagos')
@section('breadcrumb', 'Licencias y pagos')

@section('content')

    @php
        $dinero = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        $campo = 'bg-creamdark/40 border border-olive-100 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
    @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Licencias y pagos</h1>
        <p class="text-sm text-muted">Aprueba los planes que piden las ópticas y revisa el historial de licencias.</p>
    </div>

    {{-- Pendientes --}}
    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden mb-6">
        <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center">
                @include('partials.icon', ['name' => 'receipt', 'class' => 'w-4.5 h-4.5'])
            </div>
            <div>
                <h3 class="font-serif text-lg leading-tight flex items-center gap-2">
                    Pagos por aprobar
                    <span class="text-[11px] font-sans font-semibold bg-creamdark text-olive-700 rounded-full px-2 py-0.5 tabular-nums">{{ $pendientes->count() }}</span>
                </h3>
                <p class="text-xs text-muted">Al aprobar, el plan empieza hoy o cuando termine el que la óptica ya tiene.</p>
            </div>
        </div>

        @forelse ($pendientes as $lic)
            <div class="px-5 sm:px-6 py-4 border-b border-olive-100 last:border-b-0 flex flex-col lg:flex-row lg:items-center gap-4">
                <div class="lg:w-72 shrink-0">
                    <a href="{{ route('superadmin.empresas.show', $lic->nit_empresa) }}" class="font-medium hover:text-olive-700">
                        {{ $lic->empresa->nombre ?? $lic->nit_empresa }}
                    </a>
                    <p class="text-xs text-muted">
                        {{ $lic->plan->nombre ?? 'Plan' }} · {{ $dinero($lic->valor) }} · pedida el {{ $lic->fecha_solicitud?->format('d/m/Y') }}
                    </p>
                    @if ($lic->empresa?->estaPendiente())
                        <span class="inline-block mt-1 text-[10px] font-semibold uppercase text-sky-700 bg-sky-50 rounded px-1.5 py-0.5">Óptica nueva</span>
                        <p class="text-[11px] text-muted mt-1">{{ $lic->observaciones }}</p>
                        <p class="text-[11px] text-muted">Ref. {{ $lic->referencia_pago }}</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('superadmin.licencias.aprobar', $lic->id_licencia) }}" class="flex flex-wrap items-center gap-2 flex-1">
                    @csrf
                    @if ($lic->empresa?->estaPendiente())
                        <p class="text-xs text-muted flex-1 min-w-[10rem]">
                            Al aprobar se activa la óptica y se envía la contraseña a su administrador por correo.
                        </p>
                        <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2 rounded-lg">Aprobar y enviar acceso</button>
                    @else
                        <input type="text" name="referencia_pago" placeholder="Referencia del pago" class="{{ $campo }} flex-1 min-w-[10rem]">
                        <input type="number" name="valor" min="0" step="1" value="{{ (int) $lic->valor }}" title="Valor recibido" class="{{ $campo }} w-32">
                        <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2 rounded-lg">Aprobar pago</button>
                    @endif
                </form>

                <form method="POST" action="{{ route('superadmin.licencias.rechazar', $lic->id_licencia) }}"
                      onsubmit="return confirm('{{ $lic->empresa?->estaPendiente()
                          ? '¿Rechazar el registro de ' . addslashes($lic->empresa->nombre) . '? Se borrarán la óptica y su administrador.'
                          : '¿Rechazar la solicitud de ' . addslashes($lic->empresa->nombre ?? '') . '?' }}')">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-800 px-2 py-2">Rechazar</button>
                </form>
            </div>
        @empty
            <div class="px-6 py-10 text-center text-sm text-muted">No hay pagos por aprobar.</div>
        @endforelse
    </div>

    {{-- Historial --}}
    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        <div class="px-5 sm:px-6 py-4 flex items-center justify-between flex-wrap gap-3 border-b border-olive-100">
            <h3 class="font-serif text-lg">Historial de licencias</h3>
            <div class="flex items-center gap-1 bg-creamdark/70 rounded-full p-1">
                @foreach (['todas' => 'Todas', 'activa' => 'Activas', 'vencida' => 'Vencidas', 'cancelada' => 'Canceladas'] as $key => $label)
                    <a href="{{ route('superadmin.licencias.index', ['estado' => $key]) }}"
                       class="px-3.5 py-1.5 rounded-full text-xs font-medium transition
                              {{ $estado === $key ? 'bg-white text-olive-700 shadow-sm ring-1 ring-olive-100' : 'text-ink/60 hover:text-ink' }}">
                        {{ $label }}
                    </a>
                @endforeach
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
