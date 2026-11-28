@extends('layouts.optometra')

@php use App\Support\Optometria; use Illuminate\Support\Carbon; @endphp

@section('title', 'Terapia · ' . $t->nombres)
@section('breadcrumb')
    <a href="{{ route('optometra.terapias.index') }}" class="hover:underline">Terapias</a> / {{ $t->nombres }}
@endsection

@section('content')
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-muted">{{ ucfirst($t->tipo) }} · plan #{{ $t->id_agendamiento_procedimiento }}</p>
            <h1 class="font-serif text-2xl">{{ $t->nomtipo_terp ?? 'Terapia' }} — {{ $t->nombres }} {{ $t->apellido }}</h1>
            <p class="text-sm text-muted">
                Desde {{ Carbon::parse($t->fecha_inicio)->format('d/m/Y g:i a') }}
                {{ $t->fecha_final ? '· hasta ' . Carbon::parse($t->fecha_final)->format('d/m/Y') : '' }}
                · {{ $t->sesiones }}{{ $t->sesiones_plan ? ' de ' . $t->sesiones_plan : '' }} sesiones registradas
            </p>
            @if ($t->tipo_descripcion)<p class="text-xs text-muted mt-1">{{ $t->tipo_descripcion }}</p>@endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="{{ Optometria::colorEstado($t->estado) }}">{{ $t->estado }}</span>
            <a href="{{ route('optometra.historias.show', $t->id_historia_clinica) }}" class="op-btn op-btn-ghost op-btn-sm">Historia de origen</a>
            @if ($esMia)
                @foreach (Optometria::ESTADOS_TERAPIA as $e)
                    @continue($e === $t->estado)
                    <form method="POST" action="{{ route('optometra.terapias.estado', $t->id_agendamiento_procedimiento) }}">
                        @csrf
                        <input type="hidden" name="estado" value="{{ $e }}">
                        <button class="op-btn op-btn-sm {{ $e === 'Finalizado' ? 'op-btn-primary' : 'op-btn-ghost' }}">
                            {{ ['Activo' => 'Reactivar', 'Finalizado' => 'Finalizar', 'Suspendido' => 'Suspender'][$e] }}
                        </button>
                    </form>
                @endforeach
            @endif
        </div>
    </div>

    @if ($t->sesiones_plan)
        @php $pct = min(100, round($t->sesiones * 100 / max(1, $t->sesiones_plan))); @endphp
        <div class="op-card p-4 mb-6">
            <div class="flex justify-between text-xs text-muted mb-2"><span>Avance del plan</span><span>{{ $pct }}%</span></div>
            <div class="h-2 rounded-full bg-olive-100 overflow-hidden"><div class="h-2 bg-olive-600" style="width: {{ $pct }}%"></div></div>
        </div>
    @endif

    <div class="grid xl:grid-cols-3 gap-6 items-start">
        <section class="op-card overflow-hidden xl:col-span-2">
            <div class="op-card-head"><h2 class="font-semibold">Evolución por sesión</h2></div>
            @forelse ($evoluciones as $i => $ev)
                <div class="px-5 py-4 border-t border-olive-100 first:border-t-0 flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-olive-100 text-olive-700 flex items-center justify-center text-sm font-semibold shrink-0">{{ $evoluciones->count() - $i }}</div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted">{{ ucfirst(Carbon::parse($ev->fecha)->locale('es')->translatedFormat('l j \d\e F Y, g:i a')) }}</p>
                        <p class="text-sm mt-0.5 whitespace-pre-line">{{ $ev->descripcion_evolucion }}</p>
                        @if ($ev->observaciones)<p class="text-xs text-ink/70 mt-1 whitespace-pre-line">Obs.: {{ $ev->observaciones }}</p>@endif
                    </div>
                </div>
            @empty
                <p class="p-5 text-sm text-muted">Aún no hay sesiones registradas.</p>
            @endforelse
        </section>

        @if ($esMia && $t->estado === 'Activo')
            <form method="POST" action="{{ route('optometra.terapias.evolucion', $t->id_agendamiento_procedimiento) }}" class="op-card p-5 space-y-4">
                @csrf
                <h2 class="font-semibold">Registrar sesión</h2>
                @if ($errors->any() && ! $errors->has('general'))
                    <ul class="text-xs text-red-700 list-disc pl-5">@foreach ($errors->all() as $m) <li>{{ $m }}</li> @endforeach</ul>
                @endif
                <div>
                    <label class="op-label" for="ev_f">Fecha y hora</label>
                    <input id="ev_f" type="datetime-local" name="fecha" class="op-input" required value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
                <div>
                    <label class="op-label" for="ev_d">Evolución <span class="text-red-500">*</span></label>
                    <textarea id="ev_d" name="descripcion_evolucion" rows="4" class="op-input" required placeholder="Ej.: Mejora en convergencia, punto próximo de 12 a 8 cm.">{{ old('descripcion_evolucion') }}</textarea>
                </div>
                <div>
                    <label class="op-label" for="ev_o">Observaciones</label>
                    <textarea id="ev_o" name="observaciones" rows="2" class="op-input" placeholder="Ej.: Paciente colaborador, continuar ejercicios en casa.">{{ old('observaciones') }}</textarea>
                </div>
                <button class="op-btn op-btn-primary w-full justify-center">Guardar sesión</button>
            </form>
        @else
            <div class="op-card p-5 text-sm text-muted">
                {{ $esMia ? 'La terapia está ' . mb_strtolower($t->estado) . '. Reactívala para registrar nuevas sesiones.' : 'Esta terapia la lleva otro optómetra: solo puedes consultarla.' }}
            </div>
        @endif
    </div>
@endsection
