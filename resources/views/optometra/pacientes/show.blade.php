@extends('layouts.optometra')

@php
    use App\Support\Optometria;
    use Illuminate\Support\Carbon;
    $catalogo = config('simulador.condiciones');
    $ultima = $formulas->last();
    // Datos para la gráfica de evolución de la esfera
    $serie = $formulas->map(fn ($f) => [
        'fecha' => Carbon::parse($f->fecha_formula)->format('d/m/Y'),
        'od' => $f->od_esfera !== null ? (float) $f->od_esfera : null,
        'oi' => $f->oi_esfera !== null ? (float) $f->oi_esfera : null,
    ])->values();
@endphp

@section('title', $paciente->nombres . ' ' . $paciente->apellido)
@section('breadcrumb')
    <a href="{{ route('optometra.pacientes.index') }}" class="hover:underline">Pacientes</a> / {{ $paciente->nombres }}
@endsection

@push('head')
    <script defer src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
@endpush

@section('content')
    <div class="op-card p-5 sm:p-6 mb-6 flex flex-col lg:flex-row lg:items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-olive-600 text-cream flex items-center justify-center text-xl font-semibold shrink-0">
            {{ \Illuminate\Support\Str::of($paciente->nombres . ' ' . $paciente->apellido)->explode(' ')->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('') }}
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="font-serif text-2xl">{{ $paciente->nombres }} {{ $paciente->apellido }}</h1>
            <p class="text-sm text-muted">
                {{ $paciente->nom_ti_docu ?? 'Doc.' }} {{ $paciente->documento }}
                · {{ $paciente->telefono }} · {{ $paciente->email }}
                @if ($paciente->nombre_ciudad) · {{ $paciente->nombre_ciudad }} @endif
            </p>
            @if ($condiciones->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach ($condiciones as $c)
                        <span class="op-chip op-chip-info">{{ $catalogo[$c]['nombre'] ?? $c }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($porAtender)
                @if (mb_strtolower($porAtender->nom_estado) === 'en proceso')
                    <a href="{{ route('optometra.consulta.create', $porAtender->id_cita) }}" class="op-btn op-btn-primary">
                        @include('partials.icon', ['name' => 'stethoscope', 'class' => 'w-4 h-4']) Continuar consulta
                    </a>
                @else
                    <form method="POST" action="{{ route('optometra.citas.estado', $porAtender->id_cita) }}">
                        @csrf
                        <input type="hidden" name="accion" value="iniciar">
                        <button class="op-btn op-btn-primary">
                            @include('partials.icon', ['name' => 'stethoscope', 'class' => 'w-4 h-4'])
                            Atender cita del {{ Carbon::parse($porAtender->fecha_cita)->format('d/m') }}
                        </button>
                    </form>
                @endif
            @endif
            <a href="{{ route('optometra.simulador', ['paciente' => $paciente->documento]) }}" class="op-btn op-btn-ghost">
                @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4']) Simulador
            </a>
        </div>
    </div>

    <div class="grid xl:grid-cols-3 gap-6 items-start">
        <div class="xl:col-span-2 space-y-6">
            {{-- Evolución de la fórmula --}}
            <section class="op-card overflow-hidden">
                <div class="op-card-head">
                    <h2 class="font-semibold">Evolución de la fórmula</h2>
                    <span class="text-xs text-muted">{{ $formulas->count() }} {{ $formulas->count() === 1 ? 'fórmula' : 'fórmulas' }}</span>
                </div>
                @if ($formulas->isEmpty())
                    <p class="p-5 text-sm text-muted">Sin fórmulas registradas.</p>
                @else
                    @if ($formulas->count() > 1)
                        <div class="px-5 pt-4 h-56"><canvas id="graficaEsfera"></canvas></div>
                    @endif
                    <div class="overflow-x-auto p-5">
                        <table class="op-table op-table-compact">
                            <thead>
                                <tr><th>Fecha</th><th>OD esf / cil × eje</th><th>OI esf / cil × eje</th><th>Add</th><th>Corrección</th><th>Control</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($formulas->reverse() as $f)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ Carbon::parse($f->fecha_formula)->format('d/m/Y') }}</td>
                                        <td class="tabular-nums whitespace-nowrap">{{ Optometria::dioptria($f->od_esfera) }} / {{ Optometria::dioptria($f->od_cilindro) }} × {{ $f->od_eje ?? '—' }}°</td>
                                        <td class="tabular-nums whitespace-nowrap">{{ Optometria::dioptria($f->oi_esfera) }} / {{ Optometria::dioptria($f->oi_cilindro) }} × {{ $f->oi_eje ?? '—' }}°</td>
                                        <td class="tabular-nums">{{ $f->od_adc || $f->oi_adc ? Optometria::dioptria($f->od_adc) . ' / ' . Optometria::dioptria($f->oi_adc) : '—' }}</td>
                                        <td>{{ $f->correccion ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $f->proximo_control ? Carbon::parse($f->proximo_control)->format('d/m/Y') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Historias clínicas --}}
            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold">Historias clínicas</h2></div>
                @forelse ($historias as $h)
                    <a href="{{ route('optometra.historias.show', $h->id_historia_clinica) }}" class="px-5 py-3.5 flex items-center gap-4 text-sm hover:bg-olive-50 border-t border-olive-100 first:border-t-0">
                        <div class="w-20 shrink-0 text-xs text-muted">{{ Carbon::parse($h->fecha_registro)->format('d/m/Y') }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium truncate">{{ $h->nombre_enfer ? $h->codigo . ' · ' . $h->nombre_enfer : 'Sin diagnóstico' }}</p>
                            <p class="text-xs text-muted truncate">{{ $h->tratamiento ?: '—' }} · {{ $h->optometra }}</p>
                        </div>
                        @if ((string) $h->id_optometra === Optometria::doc())
                            <span class="op-chip">Tuya</span>
                        @endif
                        <span class="text-olive-700">→</span>
                    </a>
                @empty
                    <p class="p-5 text-sm text-muted">Sin historias clínicas.</p>
                @endforelse
            </section>

            {{-- Citas --}}
            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold">Citas</h2></div>
                @forelse ($citas as $c)
                    <div class="px-5 py-3 flex flex-col md:flex-row md:items-center gap-3 text-sm border-t border-olive-100 first:border-t-0">
                        <div class="w-40 shrink-0 tabular-nums whitespace-nowrap">{{ Carbon::parse($c->fecha_cita)->format('d/m/Y') }} {{ Carbon::parse($c->hora_cita)->format('g:i a') }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium truncate">{{ $c->nombre_tipo ?? 'Cita' }}{{ $c->nombre_motivo ? ' · ' . $c->nombre_motivo : '' }}</p>
                            <p class="text-xs text-muted">{{ $c->optometra }}</p>
                        </div>
                        <span class="{{ Optometria::colorEstado($c->nom_estado) }}">{{ $c->nom_estado }}</span>
                        @include('optometra.partials.acciones-cita', ['c' => $c])
                    </div>
                @empty
                    <p class="p-5 text-sm text-muted">Sin citas.</p>
                @endforelse
            </section>
        </div>

        <aside class="space-y-6">
            @if ($ultima)
                <section class="op-card overflow-hidden">
                    <div class="op-card-head"><h2 class="font-semibold text-sm">Fórmula vigente</h2><span class="text-xs text-muted">{{ Carbon::parse($ultima->fecha_formula)->format('d/m/Y') }}</span></div>
                    <div class="p-4">
                        <table class="op-rx op-rx-mini">
                            <thead><tr><th></th><th>Esf</th><th>Cil</th><th>Eje</th><th>Add</th></tr></thead>
                            <tbody>
                                @foreach (['od' => 'OD', 'oi' => 'OI'] as $o => $etq)
                                    <tr>
                                        <th>{{ $etq }}</th>
                                        <td>{{ Optometria::dioptria($ultima->{$o.'_esfera'}) }}</td>
                                        <td>{{ Optometria::dioptria($ultima->{$o.'_cilindro'}) }}</td>
                                        <td>{{ $ultima->{$o.'_eje'} ?? '—' }}</td>
                                        <td>{{ Optometria::dioptria($ultima->{$o.'_adc'}) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if ($ultima->proximo_control)
                            <p class="text-xs mt-3 {{ Carbon::parse($ultima->proximo_control)->isPast() ? 'text-rose-700' : 'text-muted' }}">
                                Próximo control: {{ Carbon::parse($ultima->proximo_control)->format('d/m/Y') }}
                                {{ Carbon::parse($ultima->proximo_control)->isPast() ? '(vencido)' : '' }}
                            </p>
                        @endif
                    </div>
                </section>
            @endif

            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold text-sm">Terapias</h2></div>
                @forelse ($terapias as $t)
                    <a href="{{ route('optometra.terapias.show', $t->id_agendamiento_procedimiento) }}" class="px-5 py-3 flex items-center justify-between gap-3 text-sm hover:bg-olive-50 border-t border-olive-100 first:border-t-0">
                        <span class="min-w-0">
                            <span class="font-medium block truncate">{{ $t->nomtipo_terp ?? ucfirst($t->tipo) }}</span>
                            <span class="text-xs text-muted">{{ $t->sesiones }} {{ $t->sesiones == 1 ? 'sesión' : 'sesiones' }} · desde {{ Carbon::parse($t->fecha_inicio)->format('d/m/Y') }}</span>
                        </span>
                        <span class="{{ Optometria::colorEstado($t->estado) }}">{{ $t->estado }}</span>
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">Sin terapias.</p>
                @endforelse
            </section>

            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold text-sm">Explicaciones con el simulador</h2></div>
                @forelse ($simulaciones as $s)
                    <div class="px-5 py-3 text-sm border-t border-olive-100 first:border-t-0">
                        <p class="font-medium">{{ $catalogo[$s->condicion]['nombre'] ?? $s->condicion }}</p>
                        <p class="text-xs text-muted">{{ Carbon::parse($s->fecha)->format('d/m/Y g:i a') }}{{ $s->observaciones ? ' · ' . $s->observaciones : '' }}</p>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">Ninguna todavía.</p>
                @endforelse
            </section>
        </aside>
    </div>
@endsection

@if ($formulas->count() > 1)
    @push('scripts')
    <script>
        window.addEventListener('load', function () {
            if (! window.Chart) return;
            const datos = {{ \Illuminate\Support\Js::from($serie) }};
            new Chart(document.getElementById('graficaEsfera'), {
                type: 'line',
                data: {
                    labels: datos.map(d => d.fecha),
                    datasets: [
                        { label: 'Esfera OD', data: datos.map(d => d.od), borderColor: '#7E6427', backgroundColor: '#7E6427', tension: .25, spanGaps: true },
                        { label: 'Esfera OI', data: datos.map(d => d.oi), borderColor: '#0EA5E9', backgroundColor: '#0EA5E9', tension: .25, spanGaps: true },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } },
                    scales: { y: { title: { display: true, text: 'Dioptrías' } } },
                },
            });
        });
    </script>
    @endpush
@endif
