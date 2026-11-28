@extends('layouts.optometra')

@php
    use App\Support\Optometria;
    use Illuminate\Support\Carbon;
    $catalogo = config('simulador.condiciones');
@endphp

@section('title', 'Historia clínica #' . $h->id_historia_clinica)
@section('breadcrumb')
    <a href="{{ route('optometra.pacientes.show', $h->documento) }}" class="hover:underline">{{ $h->nombres }}</a> / Historia #{{ $h->id_historia_clinica }}
@endsection

@section('content')
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-muted">Historia clínica #{{ $h->id_historia_clinica }}</p>
            <h1 class="font-serif text-2xl">{{ $h->nombres }} {{ $h->apellido }}</h1>
            <p class="text-sm text-muted">
                {{ $h->nom_ti_docu ?? 'Doc.' }} {{ $h->documento }} ·
                Atendido el {{ Carbon::parse($h->fecha_registro)->locale('es')->translatedFormat('j \d\e F Y, g:i a') }}
                por {{ $h->opt_nombre }} {{ $h->opt_apellido }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('optometra.historias.imprimir', $h->id_historia_clinica) }}" target="_blank" class="op-btn op-btn-ghost">
                @include('partials.icon', ['name' => 'printer', 'class' => 'w-4 h-4']) Imprimir fórmula
            </a>
            <a href="{{ route('optometra.simulador', ['historia' => $h->id_historia_clinica]) }}" class="op-btn op-btn-ghost">
                @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4']) Explicar con el simulador
            </a>
            @if ($esMia)
                <a href="{{ route('optometra.historias.edit', $h->id_historia_clinica) }}" class="op-btn op-btn-primary">
                    @include('partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4']) Editar
                </a>
            @endif
        </div>
    </div>

    <div class="grid xl:grid-cols-3 gap-6 items-start">
        <div class="xl:col-span-2 space-y-6">
            {{-- Fórmula --}}
            <section class="op-card overflow-hidden">
                <div class="op-card-head">
                    <h2 class="font-semibold">Fórmula óptica</h2>
                    @if ($formula)
                        <span class="text-xs text-muted">{{ Carbon::parse($formula->fecha_formula)->format('d/m/Y') }}</span>
                    @endif
                </div>
                @if ($formula)
                    <div class="p-5 overflow-x-auto">
                        <table class="op-rx op-rx-read">
                            <thead><tr><th></th><th>Esfera</th><th>Cilindro</th><th>Eje</th><th>Adición</th><th>DP</th><th>AV lejos</th><th>AV cerca</th></tr></thead>
                            <tbody>
                                @foreach (['od' => 'OD', 'oi' => 'OI'] as $o => $etq)
                                    <tr>
                                        <th>{{ $etq }}</th>
                                        <td>{{ Optometria::dioptria($formula->{$o.'_esfera'}) }}</td>
                                        <td>{{ Optometria::dioptria($formula->{$o.'_cilindro'}) }}</td>
                                        <td>{{ $formula->{$o.'_eje'} !== null ? $formula->{$o.'_eje'} . '°' : '—' }}</td>
                                        <td>{{ Optometria::dioptria($formula->{$o.'_adc'}) }}</td>
                                        <td>{{ $formula->{$o.'_dist_pupilar'} ?? '—' }}</td>
                                        <td>{{ $formula->{$o.'_av_lejos'} ?: '—' }}</td>
                                        <td>{{ $formula->{$o.'_av_cerca'} ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <dl class="grid sm:grid-cols-4 gap-4 mt-5 text-sm">
                            <div><dt class="text-xs text-muted">Corrección</dt><dd class="font-medium">{{ $formula->correccion ?: '—' }}</dd></div>
                            <div><dt class="text-xs text-muted">Forma de uso</dt><dd class="font-medium">{{ $formula->forma_uso ?: '—' }}</dd></div>
                            <div><dt class="text-xs text-muted">Dispositivos</dt><dd class="font-medium">{{ $formula->numero_dispositivos ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-muted">Próximo control</dt><dd class="font-medium">{{ $formula->proximo_control ? Carbon::parse($formula->proximo_control)->format('d/m/Y') : '—' }}</dd></div>
                        </dl>
                        @if ($formula->recomendaciones)
                            <p class="mt-4 text-sm"><span class="text-muted">Indicaciones:</span> {{ $formula->recomendaciones }}</p>
                        @endif
                    </div>
                @else
                    <p class="p-5 text-sm text-muted">Esta historia no tiene fórmula asociada.</p>
                @endif
            </section>

            {{-- Diagnóstico --}}
            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold">Diagnóstico</h2></div>
                @forelse ($diagnosticos as $d)
                    @php $cond = Optometria::condicionSimulador($d->codigo, $d->nombre_enfer); @endphp
                    <div class="px-5 py-3.5 border-t border-olive-100 first:border-t-0 flex flex-wrap items-center gap-3">
                        <span class="op-chip">{{ $d->codigo }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium">{{ $d->nombre_enfer }} <span class="text-muted font-normal">· {{ Optometria::OJOS[$d->ojo] ?? $d->ojo }}</span></p>
                            @if ($d->descripcion)<p class="text-xs text-muted">{{ $d->descripcion }}</p>@endif
                        </div>
                        @if ($cond)
                            <a href="{{ route('optometra.simulador', ['historia' => $h->id_historia_clinica, 'condicion' => $cond]) }}" class="op-btn op-btn-ghost op-btn-sm">
                                @include('partials.icon', ['name' => 'eye', 'class' => 'w-3.5 h-3.5']) Ver en el simulador
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="p-5 text-sm text-muted">Sin diagnóstico registrado.</p>
                @endforelse
            </section>

            {{-- Tratamiento --}}
            <section class="op-card p-5 space-y-4 text-sm">
                <div>
                    <p class="text-xs uppercase tracking-wider text-muted mb-1">Antecedentes clínicos</p>
                    <p class="whitespace-pre-line">{{ $h->antecedentes_clinicos ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-muted mb-1">Plan de tratamiento</p>
                    <p class="whitespace-pre-line">{{ $h->tratamiento ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-muted mb-1">Recomendaciones</p>
                    <p class="whitespace-pre-line">{{ $h->recomendaciones ?: '—' }}</p>
                </div>

                @if ($medicamentos->isNotEmpty())
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted mb-2">Medicamentos</p>
                        <ul class="space-y-1.5">
                            @foreach ($medicamentos as $m)
                                <li class="flex flex-wrap gap-x-2">
                                    @include('partials.icon', ['name' => 'pill', 'class' => 'w-4 h-4 text-olive-600 mt-0.5'])
                                    <b>{{ $m->nombre }}</b> × {{ $m->cantidad }} — {{ $m->dosis }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($procedimientos->isNotEmpty())
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted mb-2">Procedimientos</p>
                        <ul class="space-y-1">
                            @foreach ($procedimientos as $p)
                                <li>{{ $p->nombre }} — {{ $p->cantidad }} {{ $p->cantidad == 1 ? 'sesión' : 'sesiones' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="op-card p-5 text-sm space-y-2">
                <p class="font-semibold mb-1">Cita</p>
                <p><span class="text-muted">Fecha:</span> {{ Carbon::parse($h->fecha_cita)->format('d/m/Y') }} {{ Carbon::parse($h->hora_cita)->format('g:i a') }}</p>
                <p><span class="text-muted">Tipo:</span> {{ $h->nombre_tipo ?? '—' }}</p>
                <p><span class="text-muted">Motivo:</span> {{ $h->nombre_motivo ?? '—' }}</p>
                <p><span class="text-muted">Contacto:</span> {{ $h->telefono }} · {{ $h->email }}</p>
            </section>

            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold text-sm">Terapias de esta consulta</h2></div>
                @forelse ($terapias as $t)
                    <a href="{{ route('optometra.terapias.show', $t->id_agendamiento_procedimiento) }}" class="px-5 py-3 flex items-center justify-between gap-3 text-sm hover:bg-olive-50 border-t border-olive-100 first:border-t-0">
                        <span>
                            <span class="font-medium">{{ $t->nomtipo_terp ?? ucfirst($t->tipo) }}</span>
                            <span class="block text-xs text-muted">Desde {{ Carbon::parse($t->fecha_inicio)->format('d/m/Y') }}</span>
                        </span>
                        <span class="{{ Optometria::colorEstado($t->estado) }}">{{ $t->estado }}</span>
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">No se programaron terapias.</p>
                @endforelse
            </section>

            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold text-sm">Educación con el simulador</h2></div>
                @forelse ($simulaciones as $s)
                    <div class="px-5 py-3 text-sm border-t border-olive-100 first:border-t-0">
                        <p class="font-medium">{{ $catalogo[$s->condicion]['nombre'] ?? $s->condicion }}</p>
                        <p class="text-xs text-muted">{{ Carbon::parse($s->fecha)->format('d/m/Y g:i a') }}{{ $s->observaciones ? ' · ' . $s->observaciones : '' }}</p>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">
                        Aún no se le ha explicado con el simulador.
                        @if ($condiciones->isNotEmpty())
                            <a class="text-olive-700 font-medium hover:underline" href="{{ route('optometra.simulador', ['historia' => $h->id_historia_clinica]) }}">Abrir simulador →</a>
                        @endif
                    </p>
                @endforelse
            </section>
        </aside>
    </div>
@endsection
