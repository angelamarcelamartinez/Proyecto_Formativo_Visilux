@extends('layouts.optometra')

@section('title', 'Agenda')
@section('breadcrumb', 'Agenda')

@section('content')
    @php
        use App\Support\Optometria;
        use Illuminate\Support\Carbon;
        $hora = fn ($t) => Carbon::parse($t)->format('g:i a');
        $total = $citas->flatten(1)->count();
    @endphp

    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Mi agenda</h1>
            <p class="text-sm text-muted">
                Semana del {{ $inicio->locale('es')->translatedFormat('j \d\e F') }} al {{ $fin->locale('es')->translatedFormat('j \d\e F Y') }}
                · {{ $total }} {{ $total === 1 ? 'cita' : 'citas' }}
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2">
            <a href="{{ route('optometra.agenda', ['fecha' => $inicio->copy()->subWeek()->toDateString(), 'estado' => $estado]) }}" class="op-btn op-btn-ghost op-btn-sm">← Anterior</a>
            <a href="{{ route('optometra.agenda', ['estado' => $estado]) }}" class="op-btn op-btn-ghost op-btn-sm">Esta semana</a>
            <a href="{{ route('optometra.agenda', ['fecha' => $inicio->copy()->addWeek()->toDateString(), 'estado' => $estado]) }}" class="op-btn op-btn-ghost op-btn-sm">Siguiente →</a>
            <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="op-input op-input-sm w-auto">
            <select name="estado" class="op-input op-input-sm w-auto">
                <option value="">Todos los estados</option>
                @foreach ($estados as $e)
                    <option value="{{ $e->id_estado }}" @selected($estado == $e->id_estado)>{{ $e->nom_estado }}</option>
                @endforeach
            </select>
            <button class="op-btn op-btn-primary op-btn-sm">Filtrar</button>
        </form>
    </div>

    <div class="space-y-5">
        @for ($d = $inicio->copy(); $d->lte($fin); $d->addDay())
            @php $delDia = $citas->get($d->toDateString(), collect()); @endphp
            @continue($delDia->isEmpty() && $d->isSunday())
            <section class="op-card overflow-hidden {{ $d->isToday() ? 'ring-2 ring-olive-300' : '' }}">
                <div class="op-card-head {{ $d->isToday() ? 'bg-olive-50' : '' }}">
                    <h2 class="font-semibold text-sm">
                        {{ ucfirst($d->locale('es')->translatedFormat('l j \d\e F')) }}
                        @if ($d->isToday()) <span class="op-chip op-chip-info ml-2">Hoy</span> @endif
                    </h2>
                    <span class="text-xs text-muted">{{ $delDia->count() }} {{ $delDia->count() === 1 ? 'cita' : 'citas' }}</span>
                </div>
                @forelse ($delDia as $c)
                    <div class="px-5 py-3.5 border-t border-olive-100 first:border-t-0 flex flex-col md:flex-row md:items-center gap-3">
                        <p class="w-20 shrink-0 font-semibold tabular-nums">{{ $hora($c->hora_cita) }}</p>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium truncate">{{ $c->nombres }} {{ $c->apellido }}</p>
                            <p class="text-xs text-muted truncate">
                                {{ $c->nombre_tipo ?? 'Cita' }}{{ $c->nombre_motivo ? ' · ' . $c->nombre_motivo : '' }}
                                @if ($c->telefono) · {{ $c->telefono }} @endif
                            </p>
                        </div>
                        <span class="{{ Optometria::colorEstado($c->nom_estado) }}">{{ $c->nom_estado }}</span>
                        @include('optometra.partials.acciones-cita', ['c' => $c])
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">Sin citas.</p>
                @endforelse
            </section>
        @endfor
    </div>
@endsection
