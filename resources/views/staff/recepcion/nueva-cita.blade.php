@extends('layouts.staff')

@section('title', 'Nueva cita')
@section('breadcrumb')
    <a href="{{ route('recepcion.citas') }}" class="hover:underline">Citas</a> / Nueva
@endsection

@section('content')
    @php $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300'; @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Nueva cita</h1>
        <p class="text-sm text-muted">Escribe el documento del paciente, elige el tipo de cita y toca uno de los horarios libres. La cita queda confirmada.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 max-w-3xl rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $m) <li>{{ $m }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('recepcion.cita.guardar') }}" class="max-w-3xl space-y-6">
        @csrf

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8 space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1.5" for="n_doc">Documento del paciente <span class="text-red-500">*</span></label>
                <input id="n_doc" type="number" name="documento" value="{{ old('documento', $documento) }}" class="{{ $campo }}" required autocomplete="off">
                <p id="n_paciente" class="text-sm mt-2 text-muted"></p>
                <p class="text-xs text-muted mt-1">¿No está registrado? <a href="{{ route('recepcion.paciente.nuevo') }}" class="text-olive-600 font-semibold hover:underline">Registrar paciente</a></p>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="n_tipo">Tipo de cita <span class="text-red-500">*</span></label>
                    <select id="n_tipo" name="id_tipo_cita" class="{{ $campo }}" required>
                        <option value="">Selecciona…</option>
                        @foreach ($tiposCita as $t)
                            <option value="{{ $t->id_tipo_cita }}" @selected(old('id_tipo_cita') == $t->id_tipo_cita)>{{ $t->nombre_tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="n_motivo">Motivo <span class="text-red-500">*</span></label>
                    <select id="n_motivo" name="id_motivo" class="{{ $campo }}" required>
                        <option value="">Selecciona…</option>
                        @foreach ($motivos as $m)
                            <option value="{{ $m->id_motivo }}" @selected(old('id_motivo') == $m->id_motivo)>{{ $m->nombre_motivo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="n_fecha">Fecha <span class="text-red-500">*</span></label>
                    <input id="n_fecha" type="date" name="fecha" value="{{ old('fecha') }}" min="{{ $fechaMin }}" max="{{ $fechaMax }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="n_opt">Optómetra</label>
                    <select id="n_opt" class="{{ $campo }}">
                        <option value="">Cualquiera</option>
                        @foreach ($optometras as $o)
                            <option value="{{ $o->doc_optometra }}">{{ $o->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <p class="block text-sm font-medium mb-2">Horarios libres <span class="text-red-500">*</span></p>
                <div id="n_turnos" class="flex flex-wrap gap-2"><p class="text-sm text-muted">Elige la fecha para ver los horarios.</p></div>
                <input type="hidden" name="hora" id="n_hora" value="{{ old('hora') }}">
                <input type="hidden" name="id_optometra" id="n_idopt" value="{{ old('id_optometra') }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5" for="n_obs">Observaciones</label>
                <textarea id="n_obs" name="observaciones" rows="2" class="{{ $campo }}">{{ old('observaciones') }}</textarea>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">Agendar cita</button>
            <a href="{{ route('recepcion.citas') }}" class="text-sm text-ink/60 hover:text-ink">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    const propios = @json($optometras->pluck('doc_optometra')->map(fn ($d) => (int) $d)->values());
    const $ = (id) => document.getElementById(id);
    const claseLibre = 'px-3 py-2 rounded-lg border border-olive-100 bg-creamdark/40 text-sm tabular-nums hover:bg-olive-100 transition';
    const claseElegido = 'px-3 py-2 rounded-lg border border-olive-600 bg-olive-600 text-cream text-sm tabular-nums';

    // Datos del paciente al escribir el documento
    let t1;
    function buscarPaciente() {
        const doc = $('n_doc').value.trim();
        const p = $('n_paciente');
        if (!doc) { p.textContent = ''; return; }
        fetch(@json(route('recepcion.buscar-paciente')) + '?documento=' + encodeURIComponent(doc), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                p.textContent = d.found ? '✔ ' + d.nombres + ' ' + d.apellido + ' · ' + d.telefono : 'No hay un paciente con ese documento en esta óptica.';
                p.className = 'text-sm mt-2 ' + (d.found ? 'text-emerald-700' : 'text-rose-700');
            })
            .catch(() => { p.textContent = ''; });
    }
    $('n_doc').addEventListener('input', () => { clearTimeout(t1); t1 = setTimeout(buscarPaciente, 350); });
    if ($('n_doc').value) buscarPaciente();

    // Horarios libres de la fecha (los calcula el sitio con el horario de cada optómetra y las citas ya tomadas)
    function cargarTurnos() {
        const fecha = $('n_fecha').value, tipo = $('n_tipo').value;
        const caja = $('n_turnos');
        $('n_hora').value = ''; $('n_idopt').value = '';
        if (!fecha) { caja.innerHTML = '<p class="text-sm text-muted">Elige la fecha para ver los horarios.</p>'; return; }
        let url = @json(route('citas.horarios')) + '?fecha=' + fecha + (tipo ? '&id_tipo_cita=' + tipo : '');
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                const filtro = parseInt($('n_opt').value || '0', 10);
                const turnos = d.turnos.filter(x => propios.includes(x.id_optometra) && (!filtro || x.id_optometra === filtro));
                caja.innerHTML = '';
                if (!turnos.length) { caja.innerHTML = '<p class="text-sm text-muted">No hay horarios libres ese día' + (filtro ? ' con ese optómetra' : '') + '.</p>'; return; }
                turnos.forEach(x => {
                    const b = document.createElement('button');
                    b.type = 'button'; b.className = claseLibre;
                    b.textContent = x.hora + ' · ' + x.optometra;
                    b.addEventListener('click', () => {
                        caja.querySelectorAll('button').forEach(o => o.className = claseLibre);
                        b.className = claseElegido;
                        $('n_hora').value = x.hora; $('n_idopt').value = x.id_optometra;
                    });
                    caja.appendChild(b);
                });
            })
            .catch(() => { caja.innerHTML = '<p class="text-sm text-rose-700">No se pudieron cargar los horarios.</p>'; });
    }
    ['n_fecha', 'n_tipo', 'n_opt'].forEach(id => $(id).addEventListener('change', cargarTurnos));
    if ($('n_fecha').value) cargarTurnos();
})();
</script>
@endpush
