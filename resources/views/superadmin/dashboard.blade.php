@extends('layouts.superadmin')

@section('title', 'Panel general')
@section('breadcrumb', 'Panel general')

@section('content')

    @php
        $dinero = fn ($v) => $v >= 1000000
            ? '$' . number_format($v / 1000000, 1, ',', '.') . 'M'
            : '$' . number_format($v, 0, ',', '.');

        $dineroCompleto = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        $campo = 'bg-creamdark/40 border border-olive-100 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
    @endphp

    <div class="flex items-end justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Panel general</h1>
            <p class="text-sm text-muted">Aprueba los pagos de las ópticas y revisa las cifras clave de VisiOptica.</p>
        </div>
        <a href="{{ route('superadmin.empresas.create') }}"
           class="inline-flex items-center gap-2 bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2.5 rounded-xl shadow-card">
            @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
            Registrar óptica
        </a>
    </div>

    {{-- Pagos por aprobar --}}
    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden mb-8">
        <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center">
                @include('partials.icon', ['name' => 'receipt', 'class' => 'w-4.5 h-4.5'])
            </div>
            <div>
                <h3 class="font-serif text-lg leading-tight flex items-center gap-2">
                    Pagos por aprobar
                    <span class="text-[11px] font-sans font-semibold rounded-full px-2 py-0.5 tabular-nums
                                 {{ $pendientes->count() > 0 ? 'bg-amber-100 text-amber-700' : 'bg-creamdark text-olive-700' }}">{{ $pendientes->count() }}</span>
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
                        {{ $lic->plan->nombre ?? 'Plan' }} · {{ $dineroCompleto($lic->valor) }} · pedida el {{ $lic->fecha_solicitud?->format('d/m/Y') }}
                    </p>
                    @if ($lic->empresa?->estaPendiente())
                        <span class="inline-block mt-1 text-[10px] font-semibold uppercase text-sky-700 bg-sky-50 rounded px-1.5 py-0.5">Óptica nueva</span>
                        <p class="text-[11px] text-muted mt-1">{{ $lic->observaciones }}</p>
                        <p class="text-[11px] text-muted">Ref. {{ $lic->referencia_pago }}</p>
                    @endif
                </div>

                @if ($lic->empresa?->estaPendiente())
                    {{-- Óptica nueva: primero se verifica el NIT con la Cámara de Comercio (ventana emergente) --}}
                    <div class="flex flex-wrap items-center gap-3 flex-1">
                        <p class="text-xs text-muted flex-1 min-w-[10rem]">
                            Revisa el certificado de la Cámara de Comercio y confirma el NIT para aprobar. Se envía una contraseña temporal al correo de la óptica ({{ $lic->empresa->email }}); el administrador entra con su propio correo y debe cambiarla.
                        </p>
                        @include('superadmin.partials.modal-camara', ['empresa' => $lic->empresa, 'licencia' => $lic])
                    </div>
                @else
                    <form method="POST" action="{{ route('superadmin.licencias.aprobar', $lic->id_licencia) }}" class="flex flex-wrap items-center gap-2 flex-1">
                        @csrf
                        <input type="text" name="referencia_pago" placeholder="Referencia del pago" class="{{ $campo }} flex-1 min-w-[10rem]">
                        <input type="number" name="valor" min="0" step="1" value="{{ (int) $lic->valor }}" title="Valor recibido" class="{{ $campo }} w-32">
                        <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2 rounded-lg">Aprobar pago</button>
                    </form>
                @endif

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

    {{-- Tarjetas de resumen --}}
    @php
        $stats = [
            [
                'icon' => 'building', 'value' => $totalOpticas, 'label' => 'Ópticas registradas',
                'hint' => 'Todas las cuentas', 'tone' => 'bg-olive-100 text-olive-700', 'bar' => 'bg-olive-500',
                'link' => route('superadmin.empresas.index'),
            ],
            [
                'icon' => 'shield', 'value' => $vigentes, 'label' => 'Licencia vigente',
                'hint' => 'Más de ' . config('visioptica.dias_aviso') . ' días', 'tone' => 'bg-emerald-50 text-emerald-700', 'bar' => 'bg-emerald-500',
                'link' => route('superadmin.empresas.index', ['estado' => 'vigente']),
            ],
            [
                'icon' => 'clock', 'value' => $porVencer, 'label' => 'Por vencer',
                'hint' => config('visioptica.dias_aviso') . ' días o menos', 'tone' => 'bg-amber-50 text-amber-700', 'bar' => 'bg-amber-500',
                'link' => route('superadmin.empresas.index', ['estado' => 'por_vencer']),
            ],
            [
                'icon' => 'lock', 'value' => $vencidas, 'label' => 'Vencidas o sin plan',
                'hint' => $vencidas > 0 ? 'No pueden entrar al panel' : 'Ninguna bloqueada',
                'tone' => $vencidas > 0 ? 'bg-rose-50 text-rose-700' : 'bg-olive-100 text-olive-700',
                'bar' => $vencidas > 0 ? 'bg-rose-500' : 'bg-olive-500', 'alert' => $vencidas > 0,
                'link' => route('superadmin.empresas.index', ['estado' => 'vencida']),
            ],
            [
                'icon' => 'receipt', 'value' => $dinero($ingresosMes), 'label' => 'Ingresos del mes',
                'hint' => 'Pagos aprobados', 'tone' => 'bg-sky-50 text-sky-700', 'bar' => 'bg-sky-500',
                'link' => route('superadmin.reportes.index') . '#ingresos',
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5 gap-4 sm:gap-5">
        @foreach ($stats as $stat)
            <a href="{{ $stat['link'] }}"
               class="group relative overflow-hidden bg-white rounded-2xl border border-olive-100 shadow-card p-5 sm:p-6 flex flex-col
                      hover:-translate-y-0.5 hover:border-olive-300 hover:shadow-lg transition duration-200">
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
                    @else
                        <span class="text-[11px] font-medium text-olive-600 opacity-70 group-hover:opacity-100 transition flex items-center gap-1">
                            Ver <span class="transition group-hover:translate-x-0.5">→</span>
                        </span>
                    @endif
                </div>

                <p class="font-serif text-3xl sm:text-[34px] leading-none tabular-nums text-ink">{{ $stat['value'] }}</p>
                <p class="text-sm font-medium text-ink/80 mt-2">{{ $stat['label'] }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $stat['hint'] }}</p>
            </a>
        @endforeach
    </div>

@endsection
