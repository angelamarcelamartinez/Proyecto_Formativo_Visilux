@extends('layouts.optometra')

@php use Illuminate\Support\Carbon; @endphp

@section('title', 'Simulador visual')
@section('breadcrumb')
    Simulador visual
    @if ($paciente) / {{ $paciente->nombres }} {{ $paciente->apellido }} @endif
@endsection

@push('head')
    {{-- Ruffle: emulador de Flash (WebAssembly). La configuración va ANTES de cargar ruffle.js. --}}
    <script>
        window.RufflePlayer = window.RufflePlayer || {};
        window.RufflePlayer.config = {
            autoplay: 'on',
            unmuteOverlay: 'hidden',
            splashScreen: false,
            letterbox: 'on',
            backgroundColor: '#000000',
            contextMenu: 'rightClickOnly',
            warnOnUnsupportedContent: false,
            allowScriptAccess: false,
            openUrlMode: 'deny',
            logLevel: 'error',
            publicPath: @json(asset('assets/ruffle')) + '/',
        };
    </script>
    <script src="{{ asset('assets/ruffle/ruffle.js') }}"></script>
@endpush

@section('content')
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Simulador visual</h1>
            <p class="text-sm text-muted max-w-2xl">
                Muéstrale al paciente cómo ve con su condición y cómo puede avanzar con el tiempo. Es el simulador
                original de MSD Ophthalmics, reproducido en el navegador (no necesita Flash ni instalar nada).
            </p>
        </div>
        @if ($paciente)
            <div class="op-card px-4 py-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-olive-600 text-cream flex items-center justify-center text-sm font-semibold">
                    {{ mb_strtoupper(mb_substr($paciente->nombres, 0, 1) . mb_substr($paciente->apellido, 0, 1)) }}
                </div>
                <div class="text-sm">
                    <p class="font-semibold leading-tight">{{ $paciente->nombres }} {{ $paciente->apellido }}</p>
                    <p class="text-xs text-muted">
                        Doc. {{ $paciente->documento }}
                        @if ($historia) · <a href="{{ route('optometra.historias.show', $historia->id_historia_clinica) }}" class="text-olive-700 hover:underline">Historia #{{ $historia->id_historia_clinica }}</a> @endif
                    </p>
                </div>
            </div>
        @endif
    </div>

    <div class="grid 2xl:grid-cols-4 xl:grid-cols-3 gap-6 items-start">
        {{-- Reproductor --}}
        <section class="2xl:col-span-3 xl:col-span-2 space-y-3">
            <div id="sim-marco" class="op-sim">
                @if ($disponible)
                    <div id="sim-player" class="op-sim-player"></div>
                    <div id="sim-cargando" class="op-sim-cargando">
                        <div class="op-spinner"></div>
                        <p>Cargando el simulador…</p>
                        <p class="text-xs opacity-70">La primera vez tarda unos segundos (≈ 6 MB).</p>
                    </div>
                @else
                    <div class="op-sim-cargando">
                        <p class="font-semibold">Faltan los archivos del simulador</p>
                        <p class="text-xs opacity-80 max-w-md">Copia la carpeta <code>resources/simulador/vsmsd</code> (visionsimulator.swf y la carpeta swf) en el proyecto.</p>
                    </div>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="sim-full" class="op-btn op-btn-ghost op-btn-sm">
                    @include('partials.icon', ['name' => 'maximize', 'class' => 'w-3.5 h-3.5']) Pantalla completa
                </button>
                <button type="button" id="sim-reiniciar" class="op-btn op-btn-ghost op-btn-sm">
                    @include('partials.icon', ['name' => 'history', 'class' => 'w-3.5 h-3.5']) Volver al inicio
                </button>
                <span class="text-xs text-muted ml-auto">Activa el sonido del equipo: los videos explicativos tienen narración.</span>
            </div>
        </section>

        {{-- Guía y registro --}}
        <aside class="space-y-6">
            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold text-sm">Cómo encontrar cada condición</h2></div>
                <ol class="px-5 py-4 text-[13px] text-ink/80 space-y-1.5 list-decimal pl-9">
                    <li>Pulsa <b>inicio</b> y luego <b>simulador de visión</b> (o <b>anatomía</b>).</li>
                    <li>Arriba a la derecha elige <b>Enfermedades</b> o <b>Errores Refractivos</b>.</li>
                    <li>Escoge la escena <b>1 · 2 · 3 · 4</b> y la condición de la lista.</li>
                    <li>Mueve la barra inferior: <b>Zoom</b> y <b>Evolución de la enfermedad</b>.</li>
                </ol>
                <div class="px-5 pb-4 grid grid-cols-2 gap-2 text-xs">
                    @foreach ($escenas as $n => $nombre)
                        <div class="rounded-lg bg-creamdark/60 px-2.5 py-2"><b>{{ $n }}</b> · {{ $nombre }}</div>
                    @endforeach
                </div>
            </section>

            <section class="op-card overflow-hidden" x-data="{ sel: {{ \Illuminate\Support\Js::from($seleccion) }} }">
                <div class="op-card-head">
                    <h2 class="font-semibold text-sm">{{ $condiciones->isNotEmpty() ? 'Diagnóstico del paciente' : 'Condiciones del simulador' }}</h2>
                </div>
                <div class="divide-y divide-olive-100">
                    @foreach ($catalogo as $clave => $c)
                        @php $delPaciente = $condiciones->contains($clave); @endphp
                        @continue($condiciones->isNotEmpty() && ! $delPaciente && $clave !== $seleccion)
                        <button type="button" @click="sel = (sel === '{{ $clave }}' ? null : '{{ $clave }}')"
                                class="w-full text-left px-5 py-3 hover:bg-olive-50 transition" :class="sel === '{{ $clave }}' && 'bg-olive-50'">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-sm font-medium">{{ $c['nombre'] }}</span>
                                <span class="text-[11px] text-muted">{{ $c['grupo'] }}</span>
                            </span>
                            <span x-show="sel === '{{ $clave }}'" x-cloak class="block mt-1.5 text-xs text-ink/70 leading-relaxed">
                                {{ $c['explica'] }}<br>
                                <span class="text-olive-700">Ruta: {{ $clave === 'anatomia' ? 'inicio → anatomía' : 'inicio → simulador de visión → ' . $c['grupo'] . ' → ' . $c['nombre'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
                @if ($condiciones->isNotEmpty())
                    <a href="{{ route('optometra.simulador', array_filter(['paciente' => $paciente?->documento])) }}" class="block px-5 py-2.5 text-xs text-olive-700 hover:underline border-t border-olive-100">Ver todas las condiciones</a>
                @endif
            </section>

            @if ($tablaLista)
                <form method="POST" action="{{ route('optometra.simulador.registrar') }}" class="op-card p-5 space-y-3">
                    @csrf
                    <h2 class="font-semibold text-sm">Registrar la explicación</h2>
                    <p class="text-xs text-muted">Queda en la historia del paciente que se le explicó su condición con el simulador.</p>
                    @if ($historia)
                        <input type="hidden" name="id_historia_clinica" value="{{ $historia->id_historia_clinica }}">
                    @elseif ($paciente)
                        <input type="hidden" name="documento_paciente" value="{{ $paciente->documento }}">
                    @endif
                    <select name="condicion" class="op-input" required>
                        @foreach ($catalogo as $clave => $c)
                            <option value="{{ $clave }}" @selected(old('condicion', $seleccion) === $clave)>{{ $c['nombre'] }}</option>
                        @endforeach
                    </select>
                    <select name="escena" class="op-input">
                        <option value="">Escena (opcional)</option>
                        @foreach ($escenas as $n => $nombre)
                            <option value="{{ $n }}">{{ $n }} · {{ $nombre }}</option>
                        @endforeach
                    </select>
                    <input name="observaciones" maxlength="255" class="op-input" placeholder="Observación (opcional)">
                    <button class="op-btn op-btn-primary w-full justify-center" @disabled(! $paciente)>
                        @include('partials.icon', ['name' => 'check', 'class' => 'w-4 h-4']) Registrar
                    </button>
                    @unless ($paciente)
                        <p class="text-xs text-muted">Ábrelo desde la ficha o la historia de un paciente para poder registrarlo.</p>
                    @endunless
                </form>

                @if ($recientes->isNotEmpty())
                    <section class="op-card overflow-hidden">
                        <div class="op-card-head"><h2 class="font-semibold text-sm">Tus últimas explicaciones</h2></div>
                        @foreach ($recientes as $r)
                            <div class="px-5 py-2.5 text-sm border-t border-olive-100 first:border-t-0">
                                <p class="font-medium">{{ $catalogo[$r->condicion]['nombre'] ?? $r->condicion }}</p>
                                <p class="text-xs text-muted">{{ trim(($r->nombres ?? '') . ' ' . ($r->apellido ?? '')) ?: 'Sin paciente' }} · {{ Carbon::parse($r->fecha)->format('d/m/Y g:i a') }}</p>
                            </div>
                        @endforeach
                    </section>
                @endif
            @else
                <div class="op-card p-4 text-xs text-amber-800 bg-amber-50 border-amber-200">
                    Para registrar las explicaciones ejecuta el script <code>database/sql/2026_11_17_modulo_optometra.sql</code>.
                </div>
            @endif
        </aside>
    </div>
@endsection

@if ($disponible)
    @push('scripts')
    <script>
        (function () {
            const base = @json(url('optometra/simulador/archivos')) + '/';
            const principal = @json(config('simulador.principal'));
            const marco = document.getElementById('sim-marco');
            const cont = document.getElementById('sim-player');
            const cargando = document.getElementById('sim-cargando');
            let player = null;

            function cargar() {
                if (! window.RufflePlayer.newest) {
                    cargando.innerHTML = '<p>No se pudo cargar el reproductor (assets/ruffle/ruffle.js).</p>';
                    return;
                }
                if (player) player.remove();
                cargando.style.display = '';
                player = window.RufflePlayer.newest().createPlayer();
                player.style.width = '100%';
                player.style.height = '100%';
                cont.appendChild(player);
                // base: los .swf internos (swf/intro.swf, swf/m1_glc.swf...) se piden relativos a esta carpeta
                player.ruffle().load({ url: base + principal, base: base })
                    .then(() => { cargando.style.display = 'none'; })
                    .catch((e) => { cargando.innerHTML = '<p>No se pudo abrir el simulador.</p><p class="text-xs">' + e + '</p>'; });
            }

            document.getElementById('sim-full').addEventListener('click', () => {
                if (document.fullscreenElement) document.exitFullscreen();
                else marco.requestFullscreen?.();
            });
            document.getElementById('sim-reiniciar').addEventListener('click', cargar);

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', cargar);
            else cargar();
        })();
    </script>
    @endpush
@endif
