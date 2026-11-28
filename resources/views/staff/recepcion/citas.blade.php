@extends('layouts.staff')

@section('title', 'Citas')
@section('breadcrumb', 'Citas')

@section('content')
    @php $campo = 'bg-creamdark/40 border border-olive-100 rounded-xl py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300'; @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-serif text-2xl">Citas</h1>
            <p class="text-sm text-muted">Todas las citas de la óptica. Confirma las pendientes o cancela las que el paciente no pueda cumplir.</p>
        </div>
        <a href="{{ route('recepcion.cita.nueva') }}" class="inline-flex items-center gap-2 bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
            @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
            Nueva cita
        </a>
    </div>

    <form method="GET" class="bg-white rounded-2xl border border-olive-100 shadow-card p-4 sm:p-5 mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-medium mb-1" for="f_desde">Desde</label>
            <input id="f_desde" type="date" name="desde" value="{{ $desde }}" class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1" for="f_hasta">Hasta</label>
            <input id="f_hasta" type="date" name="hasta" value="{{ $hasta }}" class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1" for="f_estado">Estado</label>
            <select id="f_estado" name="estado" class="{{ $campo }}">
                <option value="">Todos</option>
                @foreach ($estados as $id => $nom)
                    <option value="{{ $id }}" @selected((string) $estadoSel === (string) $id)>{{ $nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1" for="f_opt">Optómetra</label>
            <select id="f_opt" name="optometra" class="{{ $campo }}">
                <option value="">Todos</option>
                @foreach ($optometras as $o)
                    <option value="{{ $o->doc_optometra }}" @selected($optometraSel == $o->doc_optometra)>{{ $o->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1" for="f_doc">Documento</label>
            <input id="f_doc" type="text" maxlength="20" name="documento" value="{{ $documento }}" placeholder="Número" class="{{ $campo }} w-36">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1" for="f_nom">Nombre</label>
            <input id="f_nom" type="text" name="nombre" value="{{ $nombre }}" placeholder="Nombre o apellido" class="{{ $campo }}">
        </div>
        <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">Filtrar</button>
        <a href="{{ route('recepcion.citas') }}" class="text-sm text-ink/60 hover:text-ink py-2.5">Limpiar</a>
        <a href="{{ route('recepcion.citas.exportar', request()->query()) }}" class="ml-auto inline-flex items-center gap-2 border border-olive-300 text-olive-700 hover:bg-olive-100 transition text-sm font-medium px-4 py-2.5 rounded-xl">Descargar Excel</a>
    </form>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        @if ($citas->isEmpty())
            @include('staff._vacio', ['titulo' => 'Sin citas en este rango', 'texto' => 'Prueba con otras fechas o agenda una nueva cita.'])
        @else
            @include('staff.recepcion._citas', ['citas' => $citas, 'mostrarFecha' => true])
        @endif
    </div>
@endsection
