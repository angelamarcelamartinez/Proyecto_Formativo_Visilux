@extends('layouts.staff')

@section('title', 'Inicio')
@section('breadcrumb', 'Inicio')

@section('content')
    @php
        $hora = now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = \Illuminate\Support\Str::of(auth()->user()->nombres)->explode(' ')->first();
        $stats = [
            ['calendar-check', $citasHoy->count(), 'Citas de hoy', 'Toda la óptica', 'bg-olive-100 text-olive-700', 'bg-olive-500', route('recepcion.citas', ['desde' => now()->toDateString(), 'hasta' => now()->toDateString()])],
            ['clock', $porConfirmar, 'Por confirmar', 'Desde hoy en adelante', 'bg-amber-50 text-amber-700', 'bg-amber-500', route('recepcion.citas', ['estado' => 3, 'desde' => now()->toDateString(), 'hasta' => now()->addDays(60)->toDateString()])],
            ['mail', $mensajes, 'Por contactar', 'Mensajes de Contáctanos', 'bg-sky-50 text-sky-700', 'bg-sky-500', route('recepcion.contacto')],
        ];
    @endphp

    <div class="relative rounded-3xl overflow-hidden mb-8 bg-ink text-cream">
        <div class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_80%_20%,#93762E,transparent_55%)]"></div>
        <div class="relative px-6 sm:px-10 py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <p class="text-[11px] uppercase tracking-[0.2em] text-olive-300">Panel de recepción</p>
                <h1 class="font-serif text-3xl sm:text-4xl mt-3">{{ $saludo }}, {{ $nombre }}</h1>
                <p class="mt-3 text-sm text-cream/70 max-w-lg leading-relaxed">Agenda las citas, confirma las pendientes y atiende los mensajes que llegan del sitio.</p>
            </div>
            <a href="{{ route('recepcion.cita.nueva') }}"
               class="inline-flex items-center justify-center gap-2 bg-olive-500 hover:bg-olive-600 transition text-cream text-sm font-medium px-5 py-3 rounded-xl shadow-lg shrink-0">
                @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                Nueva cita
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5 mb-8">
        @foreach ($stats as [$icono, $valor, $texto, $pista, $tono, $barra, $enlace])
            <a href="{{ $enlace }}" class="group relative overflow-hidden bg-white rounded-2xl border border-olive-100 shadow-card p-5 sm:p-6 flex flex-col hover:-translate-y-0.5 hover:border-olive-300 hover:shadow-lg transition duration-200">
                <span class="absolute inset-x-0 top-0 h-1 {{ $barra }} opacity-80"></span>
                <div class="w-11 h-11 rounded-xl {{ $tono }} flex items-center justify-center mb-5">
                    @include('partials.icon', ['name' => $icono, 'class' => 'w-5 h-5'])
                </div>
                <p class="font-serif text-3xl leading-none tabular-nums text-ink">{{ $valor }}</p>
                <p class="text-sm font-medium text-ink/80 mt-2">{{ $texto }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $pista }}</p>
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        <div class="px-5 sm:px-6 py-4 flex items-center justify-between border-b border-olive-100">
            <h3 class="font-serif text-lg">Citas de hoy</h3>
            <a href="{{ route('recepcion.citas') }}" class="text-xs font-semibold text-olive-600 hover:text-olive-800">Ver todas →</a>
        </div>
        @if ($citasHoy->isEmpty())
            @include('staff._vacio', ['titulo' => 'Sin citas hoy', 'texto' => 'Agenda una nueva cita para empezar.'])
        @else
            @include('staff.recepcion._citas', ['citas' => $citasHoy])
        @endif
    </div>
@endsection
