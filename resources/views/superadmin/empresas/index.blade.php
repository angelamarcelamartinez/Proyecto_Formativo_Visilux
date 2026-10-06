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
            'suspendida' => 'Suspendidas',
        ];
    @endphp

    <div class="flex items-end justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Ópticas</h1>
            <p class="text-sm text-muted">Cada cliente de VisiOptica con su plan, días de licencia y estado.</p>
        </div>
        <a href="{{ route('superadmin.empresas.create') }}"
           class="inline-flex items-center gap-2 bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2.5 rounded-xl shadow-card">
            @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
            Registrar óptica
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        <div class="px-5 sm:px-6 py-4 flex items-center justify-between flex-wrap gap-3 border-b border-olive-100">
            <div class="flex items-center gap-1 bg-creamdark/70 rounded-full p-1 overflow-x-auto">
                @foreach ($filtros as $key => $label)
                    @php $n = $key === 'todas' ? $conteos->sum() : ($conteos[$key] ?? 0); @endphp
                    <a href="{{ route('superadmin.empresas.index', array_filter(['estado' => $key, 'q' => $q])) }}"
                       class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium transition whitespace-nowrap
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
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Óptica</th>
                            <th class="py-3 pr-4 font-semibold">Correo</th>
                            <th class="py-3 pr-4 font-semibold">Plan</th>
                            <th class="py-3 pr-4 font-semibold">Desde</th>
                            <th class="py-3 pr-4 font-semibold">Vence</th>
                            <th class="py-3 pr-4 font-semibold">Días restantes</th>
                            <th class="py-3 pr-4 font-semibold">Usuarios</th>
                            <th class="py-3 pr-4 font-semibold">Estado</th>
                            <th class="py-3 pr-6 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($opticas as $o)
                            <tr class="hover:bg-cream/70 transition">
                                <td class="py-3 pl-6 pr-4">
                                    <a href="{{ route('superadmin.empresas.show', $o->nit) }}" class="font-medium whitespace-nowrap hover:text-olive-700">{{ $o->empresa }}</a>
                                    <p class="text-[11px] text-muted">NIT {{ $o->nit }}</p>
                                </td>
                                <td class="py-3 pr-4 text-ink/70 whitespace-nowrap">{{ $o->email }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap">
                                    {{ $o->plan ?? '—' }}
                                    @if ($o->es_prueba)
                                        <span class="text-[10px] font-semibold uppercase text-sky-700 bg-sky-50 rounded px-1.5 py-0.5 ml-1">Prueba</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/70 tabular-nums">
                                    {{ $o->fecha_inicio ? \Illuminate\Support\Carbon::parse($o->fecha_inicio)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/70 tabular-nums">
                                    {{ $o->fecha_fin ? \Illuminate\Support\Carbon::parse($o->fecha_fin)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    @include('partials.estado-licencia', ['dias' => $o->dias_restantes !== null ? (int) $o->dias_restantes : null, 'solo' => 'dias'])
                                </td>
                                <td class="py-3 pr-4 tabular-nums text-ink/80">{{ $o->usuarios_registrados }}</td>
                                <td class="py-3 pr-4">
                                    @include('partials.estado-licencia', ['estado' => $o->estado_licencia, 'solo' => 'estado'])
                                    @if ($o->solicitudes_pendientes > 0 && $o->estado_empresa !== 'pendiente')
                                        <a href="{{ route('superadmin.licencias.index') }}" class="block text-[11px] text-sky-700 mt-1 hover:underline">Pago por aprobar</a>
                                    @endif
                                </td>
                                <td class="py-3 pr-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('superadmin.empresas.show', $o->nit) }}" title="Ver detalle"
                                           class="p-2 rounded-lg text-ink/60 hover:bg-olive-100 hover:text-olive-700 transition">
                                            @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
                                        </a>
                                        <a href="{{ route('superadmin.empresas.edit', $o->nit) }}" title="Editar datos"
                                           class="p-2 rounded-lg text-ink/60 hover:bg-olive-100 hover:text-olive-700 transition">
                                            @include('partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4'])
                                        </a>
                                        @if ($o->estado_empresa !== 'pendiente')
                                                @include('superadmin.partials.boton-estado', ['nit' => $o->nit, 'nombre' => $o->empresa, 'suspendida' => $o->estado_empresa === 'suspendida', 'compacto' => true])
                                            @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
