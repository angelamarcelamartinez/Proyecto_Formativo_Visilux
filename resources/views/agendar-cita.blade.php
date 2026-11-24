@include('includes.header')

<!-- ======================================================
     BANNER DE LA PÁGINA
====================================================== -->
<section class="page-header">
    <span class="ph-ring r1"></span><span class="ph-ring r2"></span><span class="ph-ring r3"></span>
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Reserva en línea</span>
        <h1 class="page-title display-5 mb-3">Agenda tu Cita</h1>
        <p class="page-subtitle mx-auto">
            Escoge el día y la hora que mejor te queden entre los horarios disponibles
            de nuestros optómetras.
        </p>
        <div class="ph-chips">
            <span class="ph-chip"><i class="bi bi-calendar-check"></i> Elige tu día</span>
            <span class="ph-chip"><i class="bi bi-clock"></i> Elige tu hora</span>
            <span class="ph-chip"><i class="bi bi-eyeglasses"></i> Con optómetra</span>
        </div>
    </div>
</section>

<!-- ======================================================
     FORMULARIO DE AGENDAMIENTO
====================================================== -->
<section class="py-5">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                @if (session('cita_reservada'))
                    @php $c = session('cita_reservada'); @endphp
                    <div class="alert alert-success border-0 shadow-sm p-4 mb-4">
                        <h5 class="fw-semibold mb-2"><i class="bi bi-check-circle me-2"></i>¡Tu cita quedó reservada!</h5>
                        <p class="mb-0">
                            {{ $c['tipo'] }} el <strong>{{ $c['fecha'] }}</strong> a las <strong>{{ $c['hora'] }}</strong>
                            con {{ $c['optometra'] }}. Te esperamos 10 minutos antes.
                        </p>
                    </div>
                @endif

                @if (session('cita_recuperada'))
                    <div class="alert alert-info border-0 shadow-sm p-3 mb-4 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Ya iniciaste sesión: revisa lo que habías escogido y pulsa <strong>Reservar cita</strong> para terminar.
                    </div>
                @endif

                @if ($usuario && $proximasCitas->isNotEmpty())
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <h5 class="fw-semibold mb-3"><i class="bi bi-calendar2-week me-2"></i>Tus próximas citas</h5>
                        <ul class="list-unstyled mb-0">
                            @foreach ($proximasCitas as $pc)
                                <li class="d-flex flex-wrap justify-content-between gap-2 py-2 border-bottom">
                                    <span>
                                        <strong>{{ \Illuminate\Support\Carbon::parse($pc->fecha_cita)->locale('es')->translatedFormat('D j M Y') }}</strong>
                                        · {{ substr($pc->hora_cita, 0, 5) }} · {{ $pc->nombre_tipo }} con {{ $pc->optometra }}
                                    </span>
                                    <span class="badge rounded-pill text-bg-light border">{{ $pc->nom_estado }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @guest
                    <div class="alert alert-light border shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <span>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Para reservar necesitas una cuenta.</strong> Escoge tu motivo, día y hora; al enviar te pedimos
                            iniciar sesión o registrarte y tu cita queda guardada.
                        </span>
                        <span class="d-flex gap-2">
                            <a href="{{ route('login') }}" class="btn btn-sm btn-gradient rounded-pill px-3">Iniciar sesión</a>
                            <a href="{{ route('register') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Registrarme</a>
                        </span>
                    </div>
                @endguest

                <div class="card border-0 shadow-sm p-4 p-md-5">
                    <form method="POST" action="{{ route('citas.store') }}" id="form-cita" novalidate data-validar>
                        @csrf

                        {{-- 1. Datos de la persona (solo con sesión iniciada) --}}
                        @auth
                            <h5 class="fw-semibold mb-3"><span class="badge rounded-pill bg-dark me-2">1</span>Tus datos</h5>
                            <div class="bg-light rounded-3 p-3 mb-4 small">
                                <div><strong>{{ $usuario->nombres }} {{ $usuario->apellido }}</strong></div>
                                <div class="text-muted">Documento {{ $usuario->documento }} · {{ $usuario->email }} · {{ $usuario->telefono }}</div>
                            </div>
                        @endauth

                        {{-- 2. Motivo (el tipo de cita sale del motivo) --}}
                        <h5 class="fw-semibold mb-3"><span class="badge rounded-pill bg-dark me-2">@auth 2 @else 1 @endauth</span>¿Qué necesitas?</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="id_motivo" class="form-label fw-medium">Motivo de la cita</label>
                                <select class="form-select @error('id_motivo') is-invalid @enderror" id="id_motivo" name="id_motivo" required>
                                    <option value="" disabled {{ old('id_motivo') ? '' : 'selected' }}>Selecciona un motivo</option>
                                    @foreach ($motivos as $motivo)
                                        <option value="{{ $motivo->id_motivo }}" @selected((string) old('id_motivo') === (string) $motivo->id_motivo)>{{ $motivo->nombre_motivo }}</option>
                                    @endforeach
                                </select>
                                @error('id_motivo') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Tipo de cita</label>
                                <div class="form-control bg-light" id="tipo_cita_texto" aria-live="polite">Se elige solo según el motivo</div>
                                <input type="hidden" id="id_tipo_cita" value="">
                                <div class="form-text">Las citas duran 30 minutos; las terapias, 1 hora.</div>
                            </div>
                        </div>

                        {{-- 3. Día y hora --}}
                        <h5 class="fw-semibold mb-3"><span class="badge rounded-pill bg-dark me-2">@auth 3 @else 2 @endauth</span>Día y hora</h5>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="fecha" class="form-label fw-medium">Fecha</label>
                                <input type="text" class="form-control @error('fecha') is-invalid @enderror"
                                       id="fecha" name="fecha" value="{{ old('fecha') }}" placeholder="Elige el día" required autocomplete="off">
                                {{-- Calendario con los festivos en amarillo y bloqueados --}}
                                @include('includes.calendario-festivos', ['input' => 'fecha'])
                                @error('fecha') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <p class="small text-muted mb-2">
                                    @if (count($diasAtencion))
                                        Atendemos citas los días: <strong>{{ implode(', ', $diasAtencion) }}</strong>.
                                    @else
                                        En este momento no hay horarios de atención publicados.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <input type="hidden" name="hora" id="hora" value="{{ old('hora') }}">
                        <input type="hidden" name="id_optometra" id="id_optometra" value="{{ old('id_optometra') }}">

                        <div id="turnos" class="mb-2" data-url="{{ route('citas.horarios') }}">
                            <p class="text-muted small mb-0">Escoge una fecha para ver los horarios disponibles.</p>
                        </div>
                        @error('hora') <span class="invalid-feedback d-block mb-2">{{ $message }}</span> @enderror
                        @error('id_optometra') @if (! $errors->has('hora')) <span class="invalid-feedback d-block mb-2">{{ $message }}</span> @endif @enderror

                        <div class="mt-4">
                            <label for="comentario" class="form-label fw-medium">Comentario adicional (opcional)</label>
                            <textarea class="form-control @error('comentario') is-invalid @enderror"
                                      id="comentario" name="comentario" rows="3" maxlength="500">{{ old('comentario') }}</textarea>
                            @error('comentario') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" id="btn-cita" class="btn btn-gradient rounded-pill px-4 mt-4" disabled>
                            <i class="bi bi-calendar-check me-2"></i>
                            @auth Reservar cita @else Continuar para reservar @endauth
                        </button>
                        <p class="small text-muted mt-2 mb-0" id="ayuda-boton">Escoge un horario para continuar.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    const fecha = document.getElementById('fecha');
    const motivo = document.getElementById('id_motivo');
    const tipo = document.getElementById('id_tipo_cita');
    const tipoTexto = document.getElementById('tipo_cita_texto');
    // [id_motivo => id_tipo_cita] y [id_tipo_cita => nombre]: el tipo sale del motivo, no se escoge aparte.
    const tipoPorMotivo = @json($tipoPorMotivo);
    const nombreTipo = @json($tiposCita->map->nombre_tipo);

    function ajustarTipo() {
        const id = tipoPorMotivo[motivo.value] || '';
        tipo.value = id;
        tipoTexto.textContent = id ? (nombreTipo[id] || '') : 'Se elige solo según el motivo';
    }
    const cont = document.getElementById('turnos');
    const hora = document.getElementById('hora');
    const opt = document.getElementById('id_optometra');
    const btn = document.getElementById('btn-cita');
    const ayuda = document.getElementById('ayuda-boton');

    function seleccionar(h, o) {
        hora.value = h || '';
        opt.value = o || '';
        const listo = !!(h && o);
        btn.disabled = !listo;
        ayuda.textContent = listo ? '' : 'Escoge un horario para continuar.';
        cont.querySelectorAll('.turno-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.hora === h && b.dataset.opt === String(o));
        });
    }

    function cargar(conservar) {
        if (!fecha.value) return;
        if (!conservar) seleccionar('', '');
        if (!tipo.value) {
            cont.innerHTML = '<div class="alert alert-info small mb-0">Primero escoge el motivo de la cita para ver los horarios disponibles.</div>';
            seleccionar('', '');
            return;
        }
        cont.innerHTML = '<p class="text-muted small mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Buscando horarios...</p>';

        fetch(cont.dataset.url + '?fecha=' + encodeURIComponent(fecha.value) + '&id_tipo_cita=' + encodeURIComponent(tipo.value), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const turnos = data.turnos || [];
                if (!turnos.length) {
                    cont.innerHTML = '<div class="alert alert-warning small mb-0">No hay horarios disponibles el ' +
                        (data.dia || 'día') + ' seleccionado. Prueba con otra fecha.</div>';
                    seleccionar('', '');
                    return;
                }
                cont.innerHTML = '<p class="small fw-medium mb-2">Horarios disponibles (' + data.dia + ') · cada cita dura ' +
                    (data.minutos >= 60 ? '1 hora' : data.minutos + ' minutos') + ':</p>' +
                    '<div class="d-flex flex-wrap gap-2">' + turnos.map(t =>
                        '<button type="button" class="btn btn-outline-secondary btn-sm rounded-3 turno-btn" data-hora="' + t.hora +
                        '" data-opt="' + t.id_optometra + '"><strong>' + t.hora + '</strong><br><small>' +
                        t.optometra.replace(/</g, '&lt;') + '</small></button>').join('') + '</div>';
                if (conservar) seleccionar(hora.value, opt.value);
            })
            .catch(() => {
                cont.innerHTML = '<div class="alert alert-danger small mb-0">No se pudieron cargar los horarios. Intenta de nuevo.</div>';
            });
    }

    cont.addEventListener('click', e => {
        const b = e.target.closest('.turno-btn');
        if (b) seleccionar(b.dataset.hora, b.dataset.opt);
    });
    fecha.addEventListener('change', () => cargar(false));
    // Al cambiar el motivo cambia el tipo de cita y, con él, la duración: se recalculan los turnos
    motivo.addEventListener('change', () => { ajustarTipo(); cargar(false); });

    // Si el formulario volvió con errores (o con lo que ya había escogido), se recargan los horarios de la fecha escogida
    ajustarTipo();
    if (fecha.value) cargar(true);
})();
</script>

@include('includes.footer')
