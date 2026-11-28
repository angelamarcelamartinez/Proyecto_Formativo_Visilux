@extends('layouts.optometra')

@section('title', 'Mi día')
@section('breadcrumb', 'Mi día')

@section('content')
    @php
        use App\Support\Optometria;
        $h = (int) now()->format('G');
        $saludo = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = \Illuminate\Support\Str::of(auth()->user()->nombres ?? '')->explode(' ')->first();
        $fechaLarga = now()->locale('es')->translatedFormat('l j \d\e F');
        $hora = fn ($t) => \Illuminate\Support\Carbon::parse($t)->format('g:i a');
        $dia = fn ($f) => str_replace('.', '', \Illuminate\Support\Carbon::parse($f)->locale('es')->translatedFormat('D j M'));
    @endphp

    {{-- Encabezado --}}
    <div class="relative rounded-3xl overflow-hidden mb-8 bg-ink text-cream">
        <div class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_85%_15%,#93762E,transparent_55%)]"></div>
        <div class="relative px-6 sm:px-10 py-9 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <p class="text-[11px] uppercase tracking-[0.2em] text-olive-300">{{ ucfirst($fechaLarga) }}</p>
                <h1 class="font-serif text-3xl sm:text-4xl mt-3">{{ $saludo }}, {{ $nombre }}</h1>
                <p class="mt-3 text-sm text-cream/70 max-w-lg leading-relaxed">
                    @if ($stats['por_atender'] > 0)
                        Tienes {{ $stats['por_atender'] }} {{ $stats['por_atender'] === 1 ? 'paciente' : 'pacientes' }} por atender hoy.
                    @elseif ($stats['hoy'] > 0)
                        Ya atendiste todas las citas de hoy. ¡Buen trabajo!
                    @else
                        No tienes citas agendadas para hoy.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('optometra.agenda') }}" class="op-btn bg-olive-500 hover:bg-olive-600 text-cream">
                    @include('partials.icon', ['name' => 'calendar', 'class' => 'w-4 h-4']) Ver agenda
                </a>
                <a href="{{ route('optometra.simulador') }}" class="op-btn bg-white/10 hover:bg-white/20 text-cream border border-white/20">
                    @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4']) Simulador visual
                </a>
            </div>
        </div>
    </div>

    {{-- Indicadores --}}
    @php
        $tarjetas = [
            ['icono' => 'calendar-check', 'valor' => $stats['hoy'], 'texto' => 'Citas hoy', 'tono' => 'bg-olive-100 text-olive-700'],
            ['icono' => 'stethoscope', 'valor' => $stats['por_atender'], 'texto' => 'Por atender', 'tono' => 'bg-amber-50 text-amber-700'],
            ['icono' => 'file-text', 'valor' => $stats['mes'], 'texto' => 'Consultas este mes', 'tono' => 'bg-emerald-50 text-emerald-700'],
            ['icono' => 'activity', 'valor' => $stats['terapias'], 'texto' => 'Terapias activas', 'tono' => 'bg-sky-50 text-sky-700', 'link' => route('optometra.terapias.index')],
        ];
    @endphp
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-8">
        @foreach ($tarjetas as $t)
            <a href="{{ $t['link'] ?? '#' }}" class="op-card p-5 flex items-center gap-4 {{ isset($t['link']) ? 'hover:border-olive-300 transition' : 'pointer-events-none' }}">
                <div class="w-11 h-11 rounded-xl {{ $t['tono'] }} flex items-center justify-center shrink-0">
                    @include('partials.icon', ['name' => $t['icono'], 'class' => 'w-5 h-5'])
                </div>
                <div>
                    <p class="font-serif text-3xl leading-none tabular-nums">{{ $t['valor'] }}</p>
                    <p class="text-sm text-ink/70 mt-1">{{ $t['texto'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="grid xl:grid-cols-3 gap-6">
        {{-- Citas de hoy --}}
        <section class="op-card xl:col-span-2 overflow-hidden">
            <div class="op-card-head">
                <h2 class="font-semibold flex items-center gap-2">
                    @include('partials.icon', ['name' => 'clock', 'class' => 'w-4 h-4 text-olive-600']) Citas de hoy
                </h2>
                <a href="{{ route('optometra.agenda') }}" class="text-xs font-medium text-olive-700 hover:underline">Semana completa →</a>
            </div>

            @forelse ($citasHoy as $c)
                <div class="px-5 py-4 border-t border-olive-100 first:border-t-0 flex flex-col md:flex-row md:items-center gap-3">
                    <div class="w-20 shrink-0">
                        <p class="font-serif text-xl leading-none">{{ $hora($c->hora_cita) }}</p>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold truncate">{{ $c->nombres }} {{ $c->apellido }}</p>
                        <p class="text-xs text-muted truncate">
                            {{ $c->nombre_tipo ?? 'Cita' }}{{ $c->nombre_motivo ? ' · ' . $c->nombre_motivo : '' }} · Doc. {{ $c->id_usuario }}
                        </p>
                    </div>
                    <span class="{{ Optometria::colorEstado($c->nom_estado) }}">{{ $c->nom_estado }}</span>
                    @include('optometra.partials.acciones-cita', ['c' => $c])
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-muted">
                    @include('partials.icon', ['name' => 'calendar', 'class' => 'w-8 h-8 mx-auto mb-3 text-olive-300'])
                    No hay citas para hoy.
                </div>
            @endforelse
        </section>

        <div class="space-y-6">
            @if ($sinCerrar->isNotEmpty())
                <section class="op-card overflow-hidden border-amber-200">
                    <div class="op-card-head bg-amber-50">
                        <h2 class="font-semibold text-amber-800 text-sm">Citas pasadas sin cerrar</h2>
                        <span class="op-chip op-chip-warn">{{ $sinCerrar->count() }}</span>
                    </div>
                    <p class="px-5 pt-3 text-xs text-muted">Registra la historia o márcalas como no asistió para liberar la agenda.</p>
                    @foreach ($sinCerrar as $c)
                        <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $c->nombres }} {{ $c->apellido }}</p>
                                <p class="text-xs text-muted">{{ $dia($c->fecha_cita) }} · {{ $hora($c->hora_cita) }}</p>
                            </div>
                            @include('optometra.partials.acciones-cita', ['c' => $c])
                        </div>
                    @endforeach
                </section>
            @endif

            <section class="op-card overflow-hidden">
                <div class="op-card-head">
                    <h2 class="font-semibold text-sm">Próximos 7 días</h2>
                </div>
                @forelse ($proximas as $c)
                    <a href="{{ route('optometra.pacientes.show', $c->id_usuario) }}" class="px-5 py-3 flex items-center gap-3 text-sm hover:bg-olive-50 border-t border-olive-100 first:border-t-0">
                        <div class="w-20 text-center shrink-0 whitespace-nowrap">
                            <p class="text-[11px] uppercase text-muted">{{ $dia($c->fecha_cita) }}</p>
                            <p class="font-semibold">{{ $hora($c->hora_cita) }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ $c->nombres }} {{ $c->apellido }}</p>
                            <p class="text-xs text-muted truncate">{{ $c->nombre_tipo ?? 'Cita' }}</p>
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-6 text-sm text-muted text-center">Sin citas en los próximos días.</p>
                @endforelse
            </section>

            <section class="op-card overflow-hidden">
                <div class="op-card-head">
                    <h2 class="font-semibold text-sm">Controles en los próximos 30 días</h2>
                </div>
                @forelse ($controles as $k)
                    <a href="{{ route('optometra.pacientes.show', $k->documento) }}" class="px-5 py-3 flex items-center justify-between gap-3 text-sm hover:bg-olive-50 border-t border-olive-100 first:border-t-0">
                        <span class="font-medium truncate">{{ $k->nombres }} {{ $k->apellido }}</span>
                        <span class="text-xs text-muted shrink-0">{{ $dia($k->proximo_control) }}</span>
                    </a>
                @empty
                    <p class="px-5 py-6 text-sm text-muted text-center">Ningún paciente tuyo tiene control próximo.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
