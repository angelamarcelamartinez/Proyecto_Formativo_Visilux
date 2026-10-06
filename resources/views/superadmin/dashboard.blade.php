@extends('layouts.superadmin')

@section('title', 'Panel general')
@section('breadcrumb', 'Panel general')

@section('content')

    @php
        $hora = now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = \Illuminate\Support\Str::of($usuario->nombres ?? 'Superadmin')->explode(' ')->first();

        $dinero = fn ($v) => $v >= 1000000
            ? '$' . number_format($v / 1000000, 1, ',', '.') . 'M'
            : '$' . number_format($v, 0, ',', '.');

        $iniciales = fn ($n) => \Illuminate\Support\Str::of($n ?? '?')->explode(' ')->filter()
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
    @endphp

    {{-- Hero --}}
    <div class="relative rounded-3xl overflow-hidden mb-8 bg-ink text-cream">
        <div class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_80%_20%,#93762E,transparent_55%)]"></div>
        <div class="relative px-6 sm:px-10 py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <p class="text-[11px] uppercase tracking-[0.2em] text-olive-300">Panel general &middot; VisiOptica</p>
                <h1 class="font-serif text-3xl sm:text-4xl mt-3">{{ $saludo }}, {{ $nombre }}</h1>
                <p class="mt-3 text-sm text-cream/70 max-w-lg leading-relaxed">
                    Controla las {{ $totalOpticas }} {{ $totalOpticas === 1 ? 'óptica registrada' : 'ópticas registradas' }}:
                    sus planes, los días que les quedan de licencia y los pagos por aprobar.
                </p>
            </div>
            <a href="{{ route('superadmin.empresas.create') }}"
               class="inline-flex items-center justify-center gap-2 bg-olive-500 hover:bg-olive-600 transition text-cream text-sm font-medium px-5 py-3 rounded-xl shadow-lg shrink-0">
                @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                Registrar óptica
            </a>
        </div>
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
                'icon' => 'x', 'value' => $suspendidas, 'label' => 'Suspendidas',
                'hint' => 'Bloqueo manual', 'tone' => 'bg-stone-100 text-stone-600', 'bar' => 'bg-stone-400',
                'link' => route('superadmin.empresas.index', ['estado' => 'suspendida']),
            ],
            [
                'icon' => 'receipt', 'value' => $dinero($ingresosMes), 'label' => 'Ingresos del mes',
                'hint' => 'Pagos aprobados', 'tone' => 'bg-sky-50 text-sky-700', 'bar' => 'bg-sky-500',
                'link' => route('superadmin.licencias.index'),
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4 sm:gap-5 mb-8">
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

    <div class="grid xl:grid-cols-3 gap-6 mb-6">

        {{-- Ópticas --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
            <div class="px-5 sm:px-6 pt-5 sm:pt-6 pb-4 flex items-center justify-between flex-wrap gap-3 border-b border-olive-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center">
                        @include('partials.icon', ['name' => 'building', 'class' => 'w-4.5 h-4.5'])
                    </div>
                    <div>
                        <h3 class="font-serif text-lg leading-tight flex items-center gap-2">
                            Ópticas
                            <span class="text-[11px] font-sans font-semibold bg-creamdark text-olive-700 rounded-full px-2 py-0.5 tabular-nums">{{ $opticas->count() }}</span>
                        </h3>
                        <p class="text-xs text-muted">Plan actual y días de licencia</p>
                    </div>
                </div>
                <a href="{{ route('superadmin.empresas.index') }}"
                   class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-olive-600 hover:text-olive-800 transition">
                    Ver todas <span aria-hidden="true">→</span>
                </a>
            </div>

            @if ($opticas->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="font-serif text-lg">Aún no hay ópticas</p>
                    <p class="text-sm text-muted mt-1">Registra la primera para darle su mes de prueba.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                                <th class="py-3 pl-6 pr-4 font-semibold">Óptica</th>
                                <th class="py-3 pr-4 font-semibold">Plan</th>
                                <th class="py-3 pr-4 font-semibold">Vence</th>
                                <th class="py-3 pr-4 font-semibold">Días</th>
                                <th class="py-3 pr-4 font-semibold">Estado</th>
                                <th class="py-3 pr-6 font-semibold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-olive-100">
                            @foreach ($opticas->take(10) as $o)
                                <tr class="hover:bg-cream/70 transition">
                                    <td class="py-3 pl-6 pr-4">
                                        <a href="{{ route('superadmin.empresas.show', $o->nit) }}" class="flex items-center gap-3 group">
                                            <div class="w-8 h-8 rounded-full bg-olive-50 text-olive-700 ring-1 ring-olive-100 flex items-center justify-center text-[11px] font-semibold shrink-0">
                                                {{ $iniciales($o->empresa) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium whitespace-nowrap group-hover:text-olive-700">{{ $o->empresa }}</p>
                                                <p class="text-[11px] text-muted">NIT {{ $o->nit }} · {{ $o->usuarios_registrados }} usuarios</p>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="py-3 pr-4 whitespace-nowrap text-ink/80">{{ $o->plan ?? '—' }}</td>
                                    <td class="py-3 pr-4 whitespace-nowrap text-ink/80 tabular-nums">
                                        {{ $o->fecha_fin ? \Illuminate\Support\Carbon::parse($o->fecha_fin)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        @include('partials.estado-licencia', ['dias' => $o->dias_restantes !== null ? (int) $o->dias_restantes : null, 'solo' => 'dias'])
                                    </td>
                                    <td class="py-3 pr-4">
                                        @include('partials.estado-licencia', ['estado' => $o->estado_licencia, 'solo' => 'estado'])
                                        @if ($o->solicitudes_pendientes > 0 && $o->estado_empresa !== 'pendiente')
                                            <span class="block text-[11px] text-sky-700 mt-1">Solicitud pendiente</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-6">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('superadmin.empresas.show', $o->nit) }}" title="Ver detalle"
                                               class="p-2 rounded-lg text-ink/60 hover:bg-olive-100 hover:text-olive-700 transition">
                                                @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
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

        {{-- Columna derecha: solicitudes y alertas --}}
        <div class="space-y-6">

            <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
                <div class="px-5 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center">
                        @include('partials.icon', ['name' => 'receipt', 'class' => 'w-4.5 h-4.5'])
                    </div>
                    <div>
                        <h3 class="font-serif text-lg leading-tight">Pagos por aprobar</h3>
                        <p class="text-xs text-muted">Solicitudes de plan de las ópticas</p>
                    </div>
                </div>

                @forelse ($pendientes as $lic)
                    <div class="px-5 py-4 border-b border-olive-100 last:border-b-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-sm truncate flex items-center gap-2">
                                    {{ $lic->empresa->nombre ?? $lic->nit_empresa }}
                                    @if ($lic->empresa?->estaPendiente())
                                        <span class="text-[10px] font-semibold uppercase text-sky-700 bg-sky-50 rounded px-1.5 py-0.5 shrink-0">Óptica nueva</span>
                                    @endif
                                </p>
                                <p class="text-xs text-muted">
                                    {{ $lic->plan->nombre ?? 'Plan' }} · {{ $dinero((float) $lic->valor) }}
                                    · {{ $lic->fecha_solicitud?->locale('es')->diffForHumans() }}
                                </p>
                                @if ($lic->empresa?->estaPendiente() && $lic->observaciones)
                                    <p class="text-[11px] text-muted mt-1">{{ $lic->observaciones }} · Ref. {{ $lic->referencia_pago }}</p>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('superadmin.licencias.aprobar', $lic->id_licencia) }}" class="mt-3 flex gap-2">
                            @csrf
                            @if ($lic->empresa?->estaPendiente())
                                <button type="submit" class="flex-1 bg-olive-600 hover:bg-olive-700 transition text-cream text-xs font-medium px-3 py-1.5 rounded-lg">Aprobar y enviar acceso</button>
                            @else
                                <input type="text" name="referencia_pago" placeholder="Referencia del pago"
                                       class="flex-1 min-w-0 bg-creamdark/40 border border-olive-100 rounded-lg py-1.5 px-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-olive-300">
                                <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-xs font-medium px-3 py-1.5 rounded-lg">Aprobar</button>
                            @endif
                        </form>
                        <form method="POST" action="{{ route('superadmin.licencias.rechazar', $lic->id_licencia) }}" class="mt-1.5"
                              onsubmit="return confirm('{{ $lic->empresa?->estaPendiente()
                                  ? '¿Rechazar el registro de ' . addslashes($lic->empresa->nombre) . '? Se borrarán la óptica y su administrador.'
                                  : '¿Rechazar la solicitud de ' . addslashes($lic->empresa->nombre ?? '') . '?' }}')">
                            @csrf
                            <button type="submit" class="text-[11px] text-rose-600 hover:text-rose-800 font-medium">Rechazar solicitud</button>
                        </form>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-muted">No hay pagos por aprobar.</div>
                @endforelse
            </div>

            <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
                <div class="px-5 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                        @include('partials.icon', ['name' => 'clock', 'class' => 'w-4.5 h-4.5'])
                    </div>
                    <div class="flex-1">
                        <h3 class="font-serif text-lg leading-tight">Requieren atención</h3>
                        <p class="text-xs text-muted">Por vencer, vencidas o sin plan</p>
                    </div>
                    <form method="POST" action="{{ route('superadmin.avisos') }}">
                        @csrf
                        <button type="submit" title="Envía el correo de vencimiento a las ópticas a las que les toca hoy"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-olive-700 hover:text-olive-800 bg-olive-50 hover:bg-olive-100 rounded-lg px-2.5 py-1.5 transition">
                            @include('partials.icon', ['name' => 'mail', 'class' => 'w-3.5 h-3.5'])
                            Enviar avisos
                        </button>
                    </form>
                </div>

                @forelse ($alertas->take(6) as $o)
                    <a href="{{ route('superadmin.empresas.show', $o->nit) }}"
                       class="px-5 py-3 flex items-center justify-between gap-3 border-b border-olive-100 last:border-b-0 hover:bg-cream/70 transition">
                        <div class="min-w-0">
                            <p class="font-medium text-sm truncate">{{ $o->empresa }}</p>
                            <p class="text-xs text-muted">{{ $o->plan ?? 'Sin plan' }}</p>
                        </div>
                        @include('partials.estado-licencia', ['dias' => $o->dias_restantes !== null ? (int) $o->dias_restantes : null, 'solo' => 'dias'])
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-muted">Todas las ópticas están al día.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid xl:grid-cols-3 gap-6">

        {{-- Ingresos --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-olive-100 shadow-card p-5 sm:p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    @include('partials.icon', ['name' => 'receipt', 'class' => 'w-4.5 h-4.5'])
                </div>
                <div>
                    <h3 class="font-serif text-lg leading-tight">Ingresos por licencias</h3>
                    <p class="text-xs text-muted">Pagos aprobados en los últimos 6 meses</p>
                </div>
            </div>
            <div class="h-64"><canvas id="graficaIngresos"></canvas></div>
        </div>

        {{-- Actividad --}}
        <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
            <div class="px-5 pt-5 pb-4 border-b border-olive-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center">
                    @include('partials.icon', ['name' => 'file-text', 'class' => 'w-4.5 h-4.5'])
                </div>
                <div>
                    <h3 class="font-serif text-lg leading-tight">Actividad reciente</h3>
                    <p class="text-xs text-muted">En todas las ópticas</p>
                </div>
            </div>
            <ul class="divide-y divide-olive-100">
                @forelse ($actividad as $a)
                    <li class="px-5 py-3">
                        <p class="text-sm">{{ $a->descripcion }}</p>
                        <p class="text-[11px] text-muted mt-0.5">
                            {{ $a->empresa->nombre ?? 'VisiOptica' }}
                            @if ($a->usuario) · {{ $a->usuario->nombres }} @endif
                            · {{ $a->fecha?->locale('es')->diffForHumans() }}
                        </p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">Sin actividad registrada.</li>
                @endforelse
            </ul>
        </div>
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
