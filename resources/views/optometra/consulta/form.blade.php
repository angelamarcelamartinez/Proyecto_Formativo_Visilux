@extends('layouts.optometra')

@php
    use App\Support\Optometria;
    use Illuminate\Support\Carbon;

    $editando = (bool) $historia;
    $f = $formula;
    $campoRx = ['esfera', 'cilindro', 'eje', 'adc', 'dist_pupilar', 'av_lejos', 'av_cerca'];

    // Valores iniciales de la refracción: lo que volvió con error, lo guardado o vacío.
    $rx = [];
    foreach (['od', 'oi'] as $ojo) {
        foreach ($campoRx as $c) {
            $k = "{$ojo}_{$c}";
            $rx[$k] = (string) old($k, $f->$k ?? '');
        }
    }
    $anteriorRx = [];
    if ($anterior) {
        foreach (array_keys($rx) as $k) {
            $anteriorRx[$k] = $anterior->$k !== null ? (string) $anterior->$k : '';
        }
    }

    $diagInit = old('diagnosticos', $filasDiagnostico ?: [['id_enfermedad' => '', 'ojo' => 'AO']]);
    $medInit = old('medicamentos', $filasMedicamento);
    $procInit = old('procedimientos', $filasProcedimiento);

    // Enfermedad → condición del simulador (para ofrecer la explicación con el simulador)
    $mapaSim = $enfermedades->mapWithKeys(fn ($e) => [$e->id_enfermedad => Optometria::condicionSimulador($e->codigo, $e->nombre_enfer)])->filter();
    $nombresSim = collect(config('simulador.condiciones'))->map(fn ($c) => $c['nombre']);

    $antecedentes = old('antecedentes_clinicos', $historia->antecedentes_clinicos ?? ($ultimaHistoria->antecedentes_clinicos ?? ''));
    $proximo = old('proximo_control', $f->proximo_control ?? now()->addYear()->toDateString());
@endphp

@section('title', ($editando ? 'Editar historia' : 'Consulta') . ' · ' . $cita->nombres)
@section('breadcrumb')
    <a href="{{ route('optometra.agenda') }}" class="hover:underline">Agenda</a> /
    <a href="{{ route('optometra.pacientes.show', $cita->id_usuario) }}" class="hover:underline">{{ $cita->nombres }}</a> /
    {{ $editando ? 'Editar historia' : 'Consulta' }}
@endsection

@section('content')
    {{-- Encabezado del paciente --}}
    <div class="op-card p-5 sm:p-6 mb-6 flex flex-col md:flex-row md:items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-olive-100 text-olive-700 flex items-center justify-center shrink-0">
            @include('partials.icon', ['name' => 'stethoscope', 'class' => 'w-7 h-7'])
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-muted">{{ $editando ? 'Editando historia clínica #' . $historia->id_historia_clinica : 'Consulta en curso' }}</p>
            <h1 class="font-serif text-2xl truncate">{{ $cita->nombres }} {{ $cita->apellido }}</h1>
            <p class="text-sm text-muted">
                Doc. {{ $cita->id_usuario }} ·
                {{ ucfirst(Carbon::parse($cita->fecha_cita)->locale('es')->translatedFormat('l j \d\e F')) }}, {{ Carbon::parse($cita->hora_cita)->format('g:i a') }} ·
                {{ $cita->nombre_tipo ?? 'Cita' }}{{ $cita->nombre_motivo ? ' · Motivo: ' . $cita->nombre_motivo : '' }}
            </p>
            @if ($cita->observaciones)
                <p class="text-xs text-ink/70 mt-1">Observaciones de la cita: {{ $cita->observaciones }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('optometra.pacientes.show', $cita->id_usuario) }}" target="_blank" class="op-btn op-btn-ghost op-btn-sm">
                @include('partials.icon', ['name' => 'history', 'class' => 'w-3.5 h-3.5']) Antecedentes
            </a>
            <a href="{{ route('optometra.simulador', ['paciente' => $cita->id_usuario]) }}" target="_blank" class="op-btn op-btn-ghost op-btn-sm">
                @include('partials.icon', ['name' => 'eye', 'class' => 'w-3.5 h-3.5']) Simulador
            </a>
        </div>
    </div>

    @if ($errors->any() && ! $errors->has('general'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            <p class="font-semibold mb-1">Revisa estos datos:</p>
            <ul class="list-disc pl-5">@foreach ($errors->all() as $m) <li>{{ $m }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $editando ? route('optometra.historias.update', $historia->id_historia_clinica) : route('optometra.consulta.store', $cita->id_cita) }}"
          x-data="consulta({
              rx: {{ \Illuminate\Support\Js::from($rx) }},
              anterior: {{ \Illuminate\Support\Js::from($anteriorRx) }},
              diag: {{ \Illuminate\Support\Js::from(array_values($diagInit)) }},
              meds: {{ \Illuminate\Support\Js::from(array_values($medInit)) }},
              procs: {{ \Illuminate\Support\Js::from(array_values($procInit)) }},
              mapaSim: {{ \Illuminate\Support\Js::from($mapaSim) }},
              nombresSim: {{ \Illuminate\Support\Js::from($nombresSim) }},
          })"
          class="grid xl:grid-cols-3 gap-6 items-start">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div class="xl:col-span-2 space-y-6">

            {{-- 1. Anamnesis --}}
            <section class="op-card">
                <div class="op-card-head"><h2 class="op-section"><span class="op-step">1</span> Anamnesis y antecedentes</h2></div>
                <div class="p-5">
                    <label class="op-label" for="antecedentes">Antecedentes clínicos (personales, familiares, medicamentos, alergias)</label>
                    <textarea id="antecedentes" name="antecedentes_clinicos" rows="3" class="op-input" placeholder="Ej.: Hipertensión controlada. Madre con glaucoma. Alergia a preservantes.">{{ $antecedentes }}</textarea>
                    @if (! $editando && $ultimaHistoria)
                        <p class="text-xs text-muted mt-1.5">Se precargaron los antecedentes de la última historia ({{ Carbon::parse($ultimaHistoria->fecha_registro)->format('d/m/Y') }}). Actualízalos si cambiaron.</p>
                    @endif
                </div>
            </section>

            {{-- 2. Refracción --}}
            <section class="op-card">
                <div class="op-card-head">
                    <h2 class="op-section"><span class="op-step">2</span> Refracción final</h2>
                    @if ($anterior)
                        <button type="button" @click="copiarAnterior()" class="op-btn op-btn-ghost op-btn-sm">
                            Copiar fórmula del {{ Carbon::parse($anterior->fecha_formula)->format('d/m/Y') }}
                        </button>
                    @endif
                </div>
                <div class="p-5 overflow-x-auto">
                    <table class="op-rx">
                        <thead>
                            <tr>
                                <th></th><th>Esfera</th><th>Cilindro</th><th>Eje °</th><th>Adición</th><th>DP mm</th><th>AV lejos</th><th>AV cerca</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['od' => 'OD', 'oi' => 'OI'] as $ojo => $etq)
                                <tr>
                                    <th>{{ $etq }}</th>
                                    <td><input type="number" step="0.25" min="-30" max="30" name="{{ $ojo }}_esfera" x-model="rx.{{ $ojo }}_esfera" class="op-input op-input-rx @error($ojo.'_esfera') op-input-error @enderror" placeholder="0.00"></td>
                                    <td><input type="number" step="0.25" min="-10" max="10" name="{{ $ojo }}_cilindro" x-model="rx.{{ $ojo }}_cilindro" class="op-input op-input-rx @error($ojo.'_cilindro') op-input-error @enderror" placeholder="0.00"></td>
                                    <td><input type="number" step="1" min="0" max="180" name="{{ $ojo }}_eje" x-model="rx.{{ $ojo }}_eje" class="op-input op-input-rx @error($ojo.'_eje') op-input-error @enderror" placeholder="0"></td>
                                    <td><input type="number" step="0.25" min="0" max="4" name="{{ $ojo }}_adc" x-model="rx.{{ $ojo }}_adc" class="op-input op-input-rx @error($ojo.'_adc') op-input-error @enderror" placeholder="—"></td>
                                    <td><input type="number" step="0.5" min="20" max="40" name="{{ $ojo }}_dist_pupilar" x-model="rx.{{ $ojo }}_dist_pupilar" class="op-input op-input-rx @error($ojo.'_dist_pupilar') op-input-error @enderror" placeholder="31"></td>
                                    <td><input type="text" maxlength="20" name="{{ $ojo }}_av_lejos" x-model="rx.{{ $ojo }}_av_lejos" class="op-input op-input-rx" placeholder="20/20"></td>
                                    <td><input type="text" maxlength="20" name="{{ $ojo }}_av_cerca" x-model="rx.{{ $ojo }}_av_cerca" class="op-input op-input-rx" placeholder="0.50 M"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-muted mt-3">Dioptrías de 0.25 en 0.25 · Eje 0–180° · DP monocular · Deja vacío lo que no aplique.</p>
                </div>

                <div class="px-5 pb-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="op-label" for="correccion">Corrección</label>
                        <select id="correccion" name="correccion" class="op-input">
                            <option value="">—</option>
                            @foreach (Optometria::CORRECCIONES as $op)
                                <option @selected(old('correccion', $f->correccion ?? '') === $op)>{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="op-label" for="forma_uso">Forma de uso</label>
                        <select id="forma_uso" name="forma_uso" class="op-input">
                            <option value="">—</option>
                            @foreach (Optometria::FORMAS_USO as $op)
                                <option @selected(old('forma_uso', $f->forma_uso ?? '') === $op)>{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="op-label" for="disp">N.º de dispositivos</label>
                        <input id="disp" type="number" min="0" max="10" name="numero_dispositivos" class="op-input" value="{{ old('numero_dispositivos', $f->numero_dispositivos ?? 1) }}">
                    </div>
                    <div>
                        <label class="op-label" for="control">Próximo control</label>
                        <input id="control" type="date" name="proximo_control" class="op-input" value="{{ $proximo }}" min="{{ now()->toDateString() }}">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="op-label" for="rec_f">Indicaciones de la fórmula</label>
                        <input id="rec_f" name="recomendaciones_formula" maxlength="1000" class="op-input" placeholder="Ej.: Antirreflejo, filtro de luz azul, lente de alto índice."
                               value="{{ old('recomendaciones_formula', $f->recomendaciones ?? '') }}">
                    </div>
                </div>
            </section>

            {{-- 3. Diagnóstico --}}
            <section class="op-card">
                <div class="op-card-head">
                    <h2 class="op-section"><span class="op-step">3</span> Diagnóstico (CIE-10)</h2>
                    <button type="button" @click="diag.push({ id_enfermedad: '', ojo: 'AO' })" x-show="diag.length < 6" class="op-btn op-btn-ghost op-btn-sm">
                        @include('partials.icon', ['name' => 'plus', 'class' => 'w-3.5 h-3.5']) Agregar
                    </button>
                </div>
                <div class="p-5 space-y-3">
                    <template x-for="(d, i) in diag" :key="i">
                        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                            <select :name="`diagnosticos[${i}][id_enfermedad]`" x-model="d.id_enfermedad" class="op-input flex-1" required>
                                <option value="">Selecciona el diagnóstico…</option>
                                @foreach ($enfermedades as $e)
                                    <option value="{{ $e->id_enfermedad }}">{{ $e->codigo }} · {{ $e->nombre_enfer }}</option>
                                @endforeach
                            </select>
                            <select :name="`diagnosticos[${i}][ojo]`" x-model="d.ojo" class="op-input sm:w-44">
                                @foreach (Optometria::OJOS as $k => $v)
                                    <option value="{{ $k }}">{{ $k }} · {{ $v }}</option>
                                @endforeach
                            </select>
                            <span x-show="mapaSim[d.id_enfermedad]" class="op-chip op-chip-info whitespace-nowrap" x-cloak>
                                En el simulador
                            </span>
                            <button type="button" @click="diag.splice(i, 1)" x-show="diag.length > 1" class="op-btn op-btn-danger op-btn-sm" title="Quitar">
                                @include('partials.icon', ['name' => 'x', 'class' => 'w-3.5 h-3.5'])
                            </button>
                        </div>
                    </template>
                    <p class="text-xs text-muted" x-show="condicionesSim().length" x-cloak>
                        Puedes explicarle al paciente
                        <b x-text="condicionesSim().map(c => nombresSim[c]).join(', ')"></b>
                        con el simulador visual después de guardar.
                    </p>
                </div>
            </section>

            {{-- 4. Tratamiento --}}
            <section class="op-card">
                <div class="op-card-head"><h2 class="op-section"><span class="op-step">4</span> Tratamiento</h2></div>
                <div class="p-5 space-y-5">
                    <div>
                        <label class="op-label" for="tratamiento">Plan de tratamiento</label>
                        <textarea id="tratamiento" name="tratamiento" rows="2" class="op-input" placeholder="Ej.: Corrección con lentes monofocales y control anual.">{{ old('tratamiento', $historia->tratamiento ?? '') }}</textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="op-label mb-0">Medicamentos formulados</p>
                            <button type="button" @click="meds.push({ id_medicamento: '', cantidad: 1, dosis: '' })" class="op-btn op-btn-ghost op-btn-sm">
                                @include('partials.icon', ['name' => 'plus', 'class' => 'w-3.5 h-3.5']) Medicamento
                            </button>
                        </div>
                        <p class="text-xs text-muted" x-show="! meds.length">Sin medicamentos.</p>
                        <div class="space-y-2">
                            <template x-for="(m, i) in meds" :key="i">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <select :name="`medicamentos[${i}][id_medicamento]`" x-model="m.id_medicamento" class="op-input col-span-12 sm:col-span-5" required>
                                        <option value="">Medicamento…</option>
                                        @foreach ($medicamentosCat as $m)
                                            <option value="{{ $m->id_medicamento }}">{{ $m->nombre }}{{ $m->descripcion ? ' — ' . $m->descripcion : '' }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" min="1" max="99" :name="`medicamentos[${i}][cantidad]`" x-model="m.cantidad" class="op-input col-span-3 sm:col-span-2" title="Cantidad" required>
                                    <input type="text" maxlength="200" :name="`medicamentos[${i}][dosis]`" x-model="m.dosis" class="op-input col-span-7 sm:col-span-4" placeholder="1 gota cada 8 horas" required>
                                    <button type="button" @click="meds.splice(i, 1)" class="op-btn op-btn-danger op-btn-sm col-span-2 sm:col-span-1 justify-center">
                                        @include('partials.icon', ['name' => 'x', 'class' => 'w-3.5 h-3.5'])
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="op-label mb-0">Procedimientos</p>
                            <button type="button" @click="procs.push({ id_procedimiento: '', cantidad: 1 })" class="op-btn op-btn-ghost op-btn-sm">
                                @include('partials.icon', ['name' => 'plus', 'class' => 'w-3.5 h-3.5']) Procedimiento
                            </button>
                        </div>
                        <p class="text-xs text-muted" x-show="! procs.length">Sin procedimientos.</p>
                        <div class="space-y-2">
                            <template x-for="(p, i) in procs" :key="i">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <select :name="`procedimientos[${i}][id_procedimiento]`" x-model="p.id_procedimiento" class="op-input col-span-12 sm:col-span-8" required>
                                        <option value="">Procedimiento…</option>
                                        @foreach ($procedimientosCat as $pr)
                                            <option value="{{ $pr->id_procedimiento }}">{{ $pr->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" min="1" max="99" :name="`procedimientos[${i}][cantidad]`" x-model="p.cantidad" class="op-input col-span-10 sm:col-span-3" title="Sesiones / cantidad" required>
                                    <button type="button" @click="procs.splice(i, 1)" class="op-btn op-btn-danger op-btn-sm col-span-2 sm:col-span-1 justify-center">
                                        @include('partials.icon', ['name' => 'x', 'class' => 'w-3.5 h-3.5'])
                                    </button>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-muted mt-1.5">En terapias, la cantidad es el número de sesiones del plan.</p>
                    </div>

                </div>
            </section>

            {{-- 5. Recomendaciones --}}
            <section class="op-card">
                <div class="op-card-head"><h2 class="op-section"><span class="op-step">5</span> Recomendaciones al paciente</h2></div>
                <div class="p-5">
                    <textarea name="recomendaciones" rows="3" class="op-input" placeholder="Ej.: Pausas activas cada 20 minutos frente a pantallas. Control en 1 año.">{{ old('recomendaciones', $historia->recomendaciones ?? '') }}</textarea>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="op-btn op-btn-primary">
                    @include('partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                    {{ $editando ? 'Guardar cambios' : 'Guardar consulta y cerrar cita' }}
                </button>
                <a href="{{ $editando ? route('optometra.historias.show', $historia->id_historia_clinica) : route('optometra.dashboard') }}" class="text-sm text-muted hover:text-ink px-3 py-2">Cancelar</a>
            </div>
        </div>

        {{-- Columna derecha: referencia --}}
        <aside class="space-y-6 xl:sticky xl:top-24">
            <section class="op-card overflow-hidden">
                <div class="op-card-head"><h2 class="font-semibold text-sm">Fórmula anterior</h2></div>
                @if ($anterior)
                    <div class="p-4">
                        <p class="text-xs text-muted mb-2">{{ Carbon::parse($anterior->fecha_formula)->format('d/m/Y') }} · {{ $anterior->correccion ?? 'Sin tipo de corrección' }}</p>
                        <table class="op-rx op-rx-mini">
                            <thead><tr><th></th><th>Esf</th><th>Cil</th><th>Eje</th><th>Add</th></tr></thead>
                            <tbody>
                                @foreach (['od' => 'OD', 'oi' => 'OI'] as $o => $etq)
                                    <tr>
                                        <th>{{ $etq }}</th>
                                        <td>{{ Optometria::dioptria($anterior->{$o.'_esfera'}) }}</td>
                                        <td>{{ Optometria::dioptria($anterior->{$o.'_cilindro'}) }}</td>
                                        <td>{{ $anterior->{$o.'_eje'} ?? '—' }}</td>
                                        <td>{{ Optometria::dioptria($anterior->{$o.'_adc'}) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="p-4 text-sm text-muted">Primera fórmula del paciente.</p>
                @endif
            </section>

            <section class="op-card p-4 text-sm space-y-2">
                <p class="font-semibold">Al guardar</p>
                <ul class="text-ink/70 space-y-1.5 list-disc pl-5 text-[13px]">
                    <li>Se crea la historia clínica con su fórmula y diagnósticos.</li>
                    <li>La cita pasa a <b>Completado</b> automáticamente.</li>
                    <li>El próximo control alimenta los recordatorios del administrador.</li>
                    <li>Podrás imprimir la fórmula y abrir el simulador para explicarle el diagnóstico.</li>
                </ul>
            </section>
        </aside>
    </form>
@endsection

@push('scripts')
<script>
    function consulta(init) {
        return {
            ...init,
            copiarAnterior() {
                for (const k in this.anterior) {
                    this.rx[k] = this.anterior[k];
                }
            },
            condicionesSim() {
                return [...new Set(this.diag.map(d => this.mapaSim[d.id_enfermedad]).filter(Boolean))];
            },
        };
    }
</script>
@endpush
