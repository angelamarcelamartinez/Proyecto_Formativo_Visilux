@extends('layouts.optometra')

@section('title', 'Mi horario')
@section('breadcrumb', 'Mi horario')

@section('content')
    @php $hora = fn ($t) => \Illuminate\Support\Carbon::parse($t)->format('g:i a'); @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Mi horario de atención</h1>
        <p class="text-sm text-muted max-w-2xl">
            Estos bloques definen los turnos que los pacientes pueden reservar contigo (citas de 30 minutos y terapias de 1 hora).
            Si necesitas cambiarlos o bloquear un espacio, pídeselo al administrador de la óptica (Admin › Horarios).
        </p>
    </div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach ($dias as $dia => $bloques)
            <section class="op-card overflow-hidden">
                <div class="op-card-head">
                    <h2 class="font-semibold text-sm">{{ $dia }}</h2>
                    <span class="text-xs text-muted">{{ $bloques->count() ? $bloques->count() . ' bloque(s)' : 'Sin atención' }}</span>
                </div>
                <div class="p-4 space-y-2">
                    @forelse ($bloques as $b)
                        <div class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm
                                    {{ $b->estado === 'bloqueado' ? 'bg-rose-50 text-rose-800' : ($b->tipo_bloque === 'terapia' ? 'bg-sky-50 text-sky-800' : 'bg-olive-50 text-olive-700') }}">
                            <span class="font-medium tabular-nums">{{ $hora($b->hora_inicio) }} – {{ $hora($b->hora_fin) }}</span>
                            <span class="text-xs">
                                @if ($b->estado === 'bloqueado')
                                    Bloqueado{{ $b->motivo_bloqueo ? ': ' . $b->motivo_bloqueo : '' }}
                                @else
                                    {{ $b->tipo_bloque === 'terapia' ? 'Terapias (60 min)' : 'Citas (30 min)' }}
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-muted py-2">No tienes bloques este día.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    @if ($sinDia->isNotEmpty())
        <p class="mt-6 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
            Tienes {{ $sinDia->count() }} bloque(s) sin día asignado. Pide al administrador que los corrija para que se puedan reservar.
        </p>
    @endif
@endsection
