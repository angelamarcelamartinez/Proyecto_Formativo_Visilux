@extends('layouts.superadmin')

@section('title', 'Ópticas')
@section('breadcrumb', 'Ópticas')

@section('content')

    @php
        $filtros = [
            'todas' => 'Todas',
            'vigente' => 'Vigentes',
            'por_vencer' => 'Por vencer',
            'vencida' => 'Vencidas',
            'sin_licencia' => 'Sin licencia',
        ];
    @endphp

    <div class="flex items-end justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Ópticas</h1>
            <p class="text-sm text-muted">Cada cliente de VisiOptica con su plan, días de licencia y estado. El historial de licencias está en la pestaña Licencias.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        <div class="px-5 sm:px-6 py-4 flex items-center justify-between flex-wrap gap-3 border-b border-olive-100">
            <div class="flex flex-wrap items-center gap-1 bg-creamdark/70 rounded-2xl sm:rounded-full p-1">
                @foreach ($filtros as $key => $label)
                    @php $n = $key === 'todas' ? $conteos->sum() : ($conteos[$key] ?? 0); @endphp
                    <a href="{{ route('superadmin.empresas.index', array_filter(['estado' => $key, 'q' => $q])) }}"
                       class="px-3.5 py-1.5 rounded-full text-xs font-medium transition whitespace-nowrap
                              {{ $estado === $key ? 'bg-white text-olive-700 shadow-sm ring-1 ring-olive-100' : 'text-ink/60 hover:text-ink' }}">
                        {{ $label }} <span class="tabular-nums text-muted">{{ $n }}</span>
                    </a>
                @endforeach
            </div>
            @if ($q !== '')
                <p class="text-xs text-muted">
                    Resultados para «{{ $q }}» ·
                    <a href="{{ route('superadmin.empresas.index', ['estado' => $estado]) }}" class="text-olive-600 font-semibold">Quitar búsqueda</a>
                </p>
            @endif
        </div>

        @if ($opticas->isEmpty())
            <div class="px-6 py-14 text-center">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-creamdark text-olive-600 flex items-center justify-center mb-4">
                    @include('partials.icon', ['name' => 'building', 'class' => 'w-6 h-6'])
                </div>
                <p class="font-serif text-lg">No hay ópticas con este filtro</p>
                <p class="text-sm text-muted mt-1">Prueba con otro estado o busca por otro nombre.</p>
            </div>
        @else
            {{-- Pantallas grandes: tabla que cabe en el ancho, sin barra de desplazamiento --}}
            <div class="hidden lg:block">
                <table class="w-full text-sm table-fixed">
                    <colgroup>
                        <col class="w-[30%]"><col class="w-[14%]"><col class="w-[19%]"><col class="w-[8%]"><col class="w-[13%]"><col class="w-[16%]">
                    </colgroup>
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-5 pr-3 font-semibold">Óptica</th>
                            <th class="py-3 pr-3 font-semibold">Plan</th>
                            <th class="py-3 pr-3 font-semibold">Vigencia</th>
                            <th class="py-3 pr-3 font-semibold">Usuarios</th>
                            <th class="py-3 pr-3 font-semibold">Estado</th>
                            <th class="py-3 pr-5 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($opticas as $o)
                            <tr class="hover:bg-cream/70 transition align-top">
                                <td class="py-3 pl-5 pr-3">
                                    <a href="{{ route('superadmin.empresas.show', $o->nit) }}" class="font-medium hover:text-olive-700 break-words">{{ $o->empresa }}</a>
                                    <p class="text-[11px] text-muted">NIT {{ $o->nit }}</p>
                                    <p class="text-[11px] text-ink/60 break-all">{{ $o->email }}</p>
                                    @include('superadmin.empresas.partials.admin-info', ['lista' => $admins[$o->nit] ?? collect(), 'nit' => $o->nit])
                                </td>
                                <td class="py-3 pr-3 break-words">
                                    {{ $o->plan ?? '—' }}
                                    @if ($o->es_prueba)
                                        <span class="text-[10px] font-semibold uppercase text-sky-700 bg-sky-50 rounded px-1.5 py-0.5 ml-1">Prueba</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-3 text-ink/70 tabular-nums text-[13px]">
                                    @if ($o->fecha_inicio)
                                        {{ \Illuminate\Support\Carbon::parse($o->fecha_inicio)->format('d/m/Y') }}
                                        <span class="text-muted">→</span>
                                        {{ $o->fecha_fin ? \Illuminate\Support\Carbon::parse($o->fecha_fin)->format('d/m/Y') : '—' }}
                                        <div class="mt-1">@include('partials.estado-licencia', ['dias' => $o->dias_restantes !== null ? (int) $o->dias_restantes : null, 'solo' => 'dias'])</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-3 pr-3 tabular-nums text-ink/80">{{ $o->usuarios_registrados }}</td>
                                <td class="py-3 pr-3">
                                    @include('partials.estado-licencia', ['estado' => $o->estado_licencia, 'solo' => 'estado'])
                                </td>
                                <td class="py-2.5 pr-4">
                                    @include('superadmin.empresas.partials.acciones', ['o' => $o])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Celular y tablet: una tarjeta por óptica --}}
            <div class="lg:hidden divide-y divide-olive-100">
                @foreach ($opticas as $o)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('superadmin.empresas.show', $o->nit) }}" class="font-medium hover:text-olive-700 break-words">{{ $o->empresa }}</a>
                                <p class="text-[11px] text-muted">NIT {{ $o->nit }}</p>
                                <p class="text-[11px] text-ink/60 break-all">{{ $o->email }}</p>
                                @include('superadmin.empresas.partials.admin-info', ['lista' => $admins[$o->nit] ?? collect(), 'nit' => $o->nit])
                            </div>
                            <div class="shrink-0">
                                @include('partials.estado-licencia', ['estado' => $o->estado_licencia, 'solo' => 'estado'])
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div>
                                <dt class="text-[11px] uppercase tracking-wider text-muted">Plan</dt>
                                <dd>
                                    {{ $o->plan ?? '—' }}
                                    @if ($o->es_prueba)
                                        <span class="text-[10px] font-semibold uppercase text-sky-700 bg-sky-50 rounded px-1.5 py-0.5 ml-1">Prueba</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] uppercase tracking-wider text-muted">Usuarios</dt>
                                <dd class="tabular-nums">{{ $o->usuarios_registrados }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-[11px] uppercase tracking-wider text-muted">Vigencia</dt>
                                <dd class="tabular-nums text-ink/80">
                                    @if ($o->fecha_inicio)
                                        {{ \Illuminate\Support\Carbon::parse($o->fecha_inicio)->format('d/m/Y') }} →
                                        {{ $o->fecha_fin ? \Illuminate\Support\Carbon::parse($o->fecha_fin)->format('d/m/Y') : '—' }}
                                        <span class="ml-1">@include('partials.estado-licencia', ['dias' => $o->dias_restantes !== null ? (int) $o->dias_restantes : null, 'solo' => 'dias'])</span>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        @include('superadmin.empresas.partials.acciones', ['o' => $o, 'etiquetas' => true])
                    </div>
                @endforeach
            </div>
        @endif
    </div>

@endsection
