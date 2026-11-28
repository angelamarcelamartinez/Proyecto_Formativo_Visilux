<?php

namespace App\Http\Controllers\Optometra;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Support\Optometria;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * La consulta: al atender una cita el optómetra registra, en una sola pantalla,
 *   historia_clinica  (antecedentes, tratamiento, recomendaciones)
 *   formula_medica    (refracción OD/OI, corrección, próximo control)
 *   diagnostico       (una fila por enfermedad y ojo)
 *   detalle_formula_medica_medicamento / _procedimiento
 * Todo va en una transacción. El trigger trg_historia_clinica_ai deja la cita en "Completado".
 */
class ConsultaController extends Controller
{
    public function create(int $id): View|RedirectResponse
    {
        $cita = Optometria::miCita($id);

        if ($cita->id_historia_clinica) {
            return redirect()->route('optometra.historias.show', $cita->id_historia_clinica)
                ->with('success', 'Esta cita ya fue atendida; esta es su historia clínica.');
        }
        if ((int) $cita->id_estado === (int) Optometria::estado('Cancelado')) {
            return redirect()->route('optometra.agenda')->withErrors(['general' => 'La cita está cancelada. Reábrela si el paciente sí asistió.']);
        }

        $ultimaHistoria = DB::table('historia_clinica as hc')
            ->join('asignacion_cita as c', 'c.id_cita', '=', 'hc.id_asignacioncita')
            ->where('c.id_usuario', $cita->id_usuario)
            ->whereIn('hc.id_asignacioncita', Optometria::idsCitasOptica())
            ->orderByDesc('hc.fecha_registro')
            ->first(['hc.*']);

        return view('optometra.consulta.form', $this->datosFormulario() + [
            'cita' => $cita,
            'historia' => null,
            'formula' => null,
            'anterior' => $this->ultimaFormula($cita->id_usuario),
            'ultimaHistoria' => $ultimaHistoria,
            'filasDiagnostico' => [],
            'filasMedicamento' => [],
            'filasProcedimiento' => [],
        ]);
    }

    public function store(Request $request, int $id): RedirectResponse
    {
        $cita = Optometria::miCita($id);

        if ($cita->id_historia_clinica) {
            return redirect()->route('optometra.historias.show', $cita->id_historia_clinica);
        }

        $data = $this->validar($request, true);

        try {
            $idHistoria = DB::transaction(function () use ($data, $cita) {
                self::indicarUsuarioABd();

                $idFormula = DB::table('formula_medica')->insertGetId(
                    $this->datosFormula($data) + [
                        'documento_paciente' => $cita->id_usuario,
                        'fecha_formula' => now()->toDateString(),
                        'created_by' => self::quien(),
                    ]
                );

                $idDiagnostico = $this->guardarDetalles($idFormula, $data);

                $idHistoria = DB::table('historia_clinica')->insertGetId([
                    'fecha_registro' => now(),
                    'id_asignacioncita' => $cita->id_cita,
                    'id_formula' => $idFormula,
                    'id_diagnostico' => $idDiagnostico,
                    'antecedentes_clinicos' => $data['antecedentes_clinicos'] ?? null,
                    'tratamiento' => $data['tratamiento'] ?? null,
                    'recomendaciones' => $data['recomendaciones'] ?? null,
                    'created_by' => self::quien(),
                ]);

                return $idHistoria;
            });
        } catch (QueryException $e) {
            return back()->withInput()->withErrors(['general' => PanelController::mensajeBd($e)]);
        }

        Actividad::registrar(Auth::user()->nit_empresa, 'crear',
            "Optómetra registró la historia clínica de {$cita->nombres} {$cita->apellido} (cita #{$cita->id_cita})",
            'historia_clinica', $idHistoria);

        return redirect()->route('optometra.historias.show', $idHistoria)
            ->with('success', 'Consulta guardada. La cita quedó como Completado.');
    }

    public function show(int $id): View
    {
        $h = Optometria::historia($id);

        $formula = $h->id_formula ? DB::table('formula_medica')->where('id_formula', $h->id_formula)->first() : null;
        $diagnosticos = Optometria::diagnosticos($h->id_formula);

        $condiciones = $diagnosticos
            ->map(fn ($d) => Optometria::condicionSimulador($d->codigo, $d->nombre_enfer))
            ->filter()->unique()->values();

        return view('optometra.historias.show', [
            'h' => $h,
            'formula' => $formula,
            'diagnosticos' => $diagnosticos,
            'medicamentos' => $this->medicamentos($h->id_formula),
            'procedimientos' => $this->procedimientos($h->id_formula),
            'terapias' => DB::table('agendamiento_procedimiento as ap')
                ->leftJoin('tipo_terapia as tt', 'tt.id_tipo_terapia', '=', 'ap.id_tipo_terapia')
                ->where('ap.id_historia_clinica', $id)
                ->get(['ap.*', 'tt.nomtipo_terp']),
            'simulaciones' => Optometria::tieneSimulaciones()
                ? DB::table('simulacion_visual')->where('id_historia_clinica', $id)->orderByDesc('fecha')->get()
                : collect(),
            'condiciones' => $condiciones,
            'esMia' => (string) $h->id_optometra === Optometria::doc(),
        ]);
    }

    public function edit(int $id): View
    {
        $h = Optometria::historia($id, true);
        $formula = $h->id_formula ? DB::table('formula_medica')->where('id_formula', $h->id_formula)->first() : null;

        $cita = Optometria::miCita((int) $h->id_asignacioncita);

        return view('optometra.consulta.form', $this->datosFormulario() + [
            'cita' => $cita,
            'historia' => $h,
            'formula' => $formula,
            'anterior' => $this->ultimaFormula($h->id_usuario, $h->id_formula),
            'ultimaHistoria' => null,
            'filasDiagnostico' => Optometria::diagnosticos($h->id_formula)
                ->map(fn ($d) => ['id_enfermedad' => $d->id_enfermedad, 'ojo' => $d->ojo])->values()->all(),
            'filasMedicamento' => $this->medicamentos($h->id_formula)
                ->map(fn ($m) => ['id_medicamento' => $m->id_medicamento, 'cantidad' => $m->cantidad, 'dosis' => $m->dosis])->values()->all(),
            'filasProcedimiento' => $this->procedimientos($h->id_formula)
                ->map(fn ($p) => ['id_procedimiento' => $p->id_procedimiento, 'cantidad' => $p->cantidad])->values()->all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $h = Optometria::historia($id, true);
        $data = $this->validar($request, false);

        try {
            DB::transaction(function () use ($h, $data) {
                $idFormula = $h->id_formula;

                // Auditoría: antes de cambiar, se guarda cómo estaban la historia y la fórmula.
                // Si la base tiene los triggers trg_hc_audit_upd / trg_fm_audit_upd, ellos ya guardan la versión
                // anterior; solo hay que decirles quién edita (así no queda cada versión duplicada en el log).
                self::indicarUsuarioABd();
                self::guardarVersionAnterior('historia_clinica', 'id_historia_clinica', $h->id_historia_clinica);
                if ($idFormula) {
                    self::guardarVersionAnterior('formula_medica', 'id_formula', $idFormula);
                }

                if ($idFormula) {
                    DB::table('formula_medica')->where('id_formula', $idFormula)->update($this->datosFormula($data));
                } else {
                    $idFormula = DB::table('formula_medica')->insertGetId($this->datosFormula($data) + [
                        'documento_paciente' => $h->id_usuario,
                        'fecha_formula' => now()->toDateString(),
                    ]);
                }

                // Se suelta el diagnóstico principal antes de reemplazar los diagnósticos.
                DB::table('historia_clinica')->where('id_historia_clinica', $h->id_historia_clinica)
                    ->update(['id_diagnostico' => null, 'id_formula' => $idFormula]);

                DB::table('diagnostico')->where('id_formula', $idFormula)->delete();
                DB::table('detalle_formula_medica_medicamento')->where('id_formula', $idFormula)->delete();
                DB::table('detalle_formula_medica_procedimiento')->where('id_formula', $idFormula)->delete();

                $idDiagnostico = $this->guardarDetalles($idFormula, $data);

                DB::table('historia_clinica')->where('id_historia_clinica', $h->id_historia_clinica)->update([
                    'id_diagnostico' => $idDiagnostico,
                    'antecedentes_clinicos' => $data['antecedentes_clinicos'] ?? null,
                    'tratamiento' => $data['tratamiento'] ?? null,
                    'recomendaciones' => $data['recomendaciones'] ?? null,
                ]);
            });
        } catch (QueryException $e) {
            return back()->withInput()->withErrors(['general' => PanelController::mensajeBd($e)]);
        }

        Actividad::registrar(Auth::user()->nit_empresa, 'editar',
            "Optómetra actualizó la historia clínica #{$h->id_historia_clinica} de {$h->nombres}", 'historia_clinica', $h->id_historia_clinica);

        return redirect()->route('optometra.historias.show', $h->id_historia_clinica)->with('success', 'Historia clínica actualizada.');
    }

    /** Fórmula lista para imprimir o guardar como PDF desde el navegador. */
    public function imprimir(int $id): View
    {
        $h = Optometria::historia($id);
        $empresa = Auth::user()->empresa;

        return view('optometra.historias.imprimir', [
            'h' => $h,
            'formula' => $h->id_formula ? DB::table('formula_medica')->where('id_formula', $h->id_formula)->first() : null,
            'diagnosticos' => Optometria::diagnosticos($h->id_formula),
            'medicamentos' => $this->medicamentos($h->id_formula),
            'empresa' => $empresa,
            'pagina' => $empresa ? DB::table('pagina_empresa')->where('nit_empresa', $empresa->nit)->first() : null,
        ]);
    }

    // ------------------------------------------------------------------

    protected function datosFormulario(): array
    {
        return [
            'enfermedades' => DB::table('enfermedades')->orderBy('nombre_enfer')->get(['id_enfermedad', 'nombre_enfer', 'codigo']),
            'medicamentosCat' => DB::table('medicamento')->where('id_estado', 1)->orderBy('nombre')->get(['id_medicamento', 'nombre', 'descripcion']),
            'procedimientosCat' => DB::table('procedimiento')->orderBy('nombre')->get(['id_procedimiento', 'nombre']),
        ];
    }

    protected function validar(Request $request, bool $nueva): array
    {
        $dioptria = fn (int $max) => ['nullable', 'numeric', "between:-{$max},{$max}", 'multiple_of:0.25'];
        $eje = ['nullable', 'integer', 'between:0,180'];
        $dp = ['nullable', 'numeric', 'between:20,40'];
        $av = ['nullable', 'string', 'max:20'];

        $reglas = [
            'antecedentes_clinicos' => ['nullable', 'string', 'max:3000'],
            'tratamiento' => ['nullable', 'string', 'max:3000'],
            'recomendaciones' => ['nullable', 'string', 'max:3000'],

            'od_esfera' => $dioptria(30), 'oi_esfera' => $dioptria(30),
            'od_cilindro' => $dioptria(10), 'oi_cilindro' => $dioptria(10),
            'od_eje' => $eje, 'oi_eje' => $eje,
            'od_adc' => ['nullable', 'numeric', 'between:0,4', 'multiple_of:0.25'],
            'oi_adc' => ['nullable', 'numeric', 'between:0,4', 'multiple_of:0.25'],
            'od_dist_pupilar' => $dp, 'oi_dist_pupilar' => $dp,
            'od_av_lejos' => $av, 'oi_av_lejos' => $av, 'od_av_cerca' => $av, 'oi_av_cerca' => $av,

            'correccion' => ['nullable', Rule::in(Optometria::CORRECCIONES)],
            'forma_uso' => ['nullable', Rule::in(Optometria::FORMAS_USO)],
            'numero_dispositivos' => ['nullable', 'integer', 'between:0,10'],
            'proximo_control' => ['nullable', 'date', 'after_or_equal:today'],
            'recomendaciones_formula' => ['nullable', 'string', 'max:1000'],

            'diagnosticos' => ['required', 'array', 'min:1', 'max:6'],
            'diagnosticos.*.id_enfermedad' => ['required', 'integer', 'exists:enfermedades,id_enfermedad'],
            'diagnosticos.*.ojo' => ['required', Rule::in(array_keys(Optometria::OJOS))],

            'medicamentos' => ['nullable', 'array', 'max:10'],
            'medicamentos.*.id_medicamento' => ['required', 'integer', 'exists:medicamento,id_medicamento'],
            'medicamentos.*.cantidad' => ['required', 'integer', 'between:1,99'],
            'medicamentos.*.dosis' => ['required', 'string', 'max:200'],

            'procedimientos' => ['nullable', 'array', 'max:10'],
            'procedimientos.*.id_procedimiento' => ['required', 'integer', 'exists:procedimiento,id_procedimiento'],
            'procedimientos.*.cantidad' => ['required', 'integer', 'between:1,99'],
        ];

        return $request->validate($reglas, [
            'diagnosticos.required' => 'Agrega al menos un diagnóstico.',
            'proximo_control.after_or_equal' => 'El próximo control debe ser hoy o una fecha futura.',
            'multiple_of' => 'Los valores en dioptrías van de 0.25 en 0.25.',
        ], [
            'od_esfera' => 'esfera OD', 'oi_esfera' => 'esfera OI', 'od_cilindro' => 'cilindro OD', 'oi_cilindro' => 'cilindro OI',
            'od_eje' => 'eje OD', 'oi_eje' => 'eje OI', 'od_adc' => 'adición OD', 'oi_adc' => 'adición OI',
            'od_dist_pupilar' => 'DP OD', 'oi_dist_pupilar' => 'DP OI',
            'diagnosticos.*.id_enfermedad' => 'diagnóstico', 'diagnosticos.*.ojo' => 'ojo',
            'medicamentos.*.id_medicamento' => 'medicamento', 'medicamentos.*.cantidad' => 'cantidad', 'medicamentos.*.dosis' => 'dosis',
            'procedimientos.*.id_procedimiento' => 'procedimiento', 'procedimientos.*.cantidad' => 'número de sesiones',
        ]);
    }

    protected function datosFormula(array $data): array
    {
        $campos = ['od_esfera', 'od_cilindro', 'od_eje', 'od_adc', 'od_dist_pupilar', 'od_av_lejos', 'od_av_cerca',
            'oi_esfera', 'oi_cilindro', 'oi_eje', 'oi_adc', 'oi_dist_pupilar', 'oi_av_lejos', 'oi_av_cerca',
            'correccion', 'forma_uso', 'numero_dispositivos', 'proximo_control'];

        $fila = [];
        foreach ($campos as $c) {
            $v = $data[$c] ?? null;
            $fila[$c] = ($v === '' ? null : $v);
        }
        $fila['recomendaciones'] = $data['recomendaciones_formula'] ?? null;

        // Si la BD tiene formula_medica.doc_optometra (script visioptica_Corregido.sql),
        // queda registrado qué optómetra firmó la fórmula. Sin esa columna no se envía.
        if (\Illuminate\Support\Facades\Schema::hasColumn('formula_medica', 'doc_optometra')) {
            $fila['doc_optometra'] = (string) Optometria::doc();
        }

        return $fila;
    }

    /** Guarda diagnósticos, medicamentos y procedimientos. Devuelve el id del diagnóstico principal. */
    protected function guardarDetalles(int $idFormula, array $data): ?int
    {
        $principal = null;

        foreach ($data['diagnosticos'] as $d) {
            $idDiag = DB::table('diagnostico')->insertGetId([
                'id_enfermedad' => $d['id_enfermedad'],
                'id_formula' => $idFormula,
                'ojo' => $d['ojo'],
            ]);
            $principal ??= $idDiag;
        }

        foreach ($data['medicamentos'] ?? [] as $m) {
            DB::table('detalle_formula_medica_medicamento')->insert([
                'id_formula' => $idFormula,
                'id_medicamento' => $m['id_medicamento'],
                'cantidad' => $m['cantidad'],
                'dosis' => $m['dosis'],
            ]);
        }

        foreach ($data['procedimientos'] ?? [] as $p) {
            DB::table('detalle_formula_medica_procedimiento')->insert([
                'id_formula' => $idFormula,
                'id_procedimiento' => $p['id_procedimiento'],
                'cantidad' => $p['cantidad'],
            ]);
        }

        return $principal;
    }

    protected function ultimaFormula(string $documento, ?int $excepto = null): ?object
    {
        return DB::table('formula_medica')
            ->where('documento_paciente', $documento)
            ->when($excepto, fn ($q) => $q->where('id_formula', '<', $excepto))
            ->orderByDesc('fecha_formula')->orderByDesc('id_formula')
            ->first();
    }

    protected function medicamentos(?int $idFormula)
    {
        return $idFormula ? DB::table('detalle_formula_medica_medicamento as d')
            ->join('medicamento as m', 'm.id_medicamento', '=', 'd.id_medicamento')
            ->where('d.id_formula', $idFormula)
            ->get(['d.*', 'm.nombre', 'm.descripcion']) : collect();
    }

    protected function procedimientos(?int $idFormula)
    {
        return $idFormula ? DB::table('detalle_formula_medica_procedimiento as d')
            ->join('procedimiento as p', 'p.id_procedimiento', '=', 'd.id_procedimiento')
            ->where('d.id_formula', $idFormula)
            ->get(['d.*', 'p.nombre']) : collect();
    }

    /** Quién hace el cambio, para las columnas created_by y la bitácora: "O:<documento del optómetra>". */
    protected static function quien(): string
    {
        return 'O:' . Optometria::doc();
    }

    /**
     * Guarda en log_historia_clinica una copia de la fila tal como estaba antes de editarla
     * (la historia clínica es un dato que no se puede perder: Res. 839/2017).
     */
    protected static function guardarVersionAnterior(string $tabla, string $llave, int $id): void
    {
        // La base ya lo hace con su trigger: no se repite.
        if (self::triggerExiste($tabla === 'historia_clinica' ? 'trg_hc_audit_upd' : 'trg_fm_audit_upd')) {
            return;
        }

        $antes = DB::table($tabla)->where($llave, $id)->first();
        if (! $antes) {
            return;
        }

        DB::table('log_historia_clinica')->insert([
            'tabla' => $tabla,
            'id_registro' => $id,
            'operacion' => 'UPDATE',
            'datos_anteriores' => json_encode($antes, JSON_UNESCAPED_UNICODE),
            'usuario' => self::quien(),
            'fecha_hora' => now(),
        ]);
    }

    /** Le dice a los triggers de auditoría quién hace el cambio (variable de la conexión actual: @app_usuario). */
    protected static function indicarUsuarioABd(): void
    {
        DB::statement('SET @app_usuario = ?', [self::quien()]);
    }

    /** ¿Existe ese trigger en la base actual? */
    protected static function triggerExiste(string $nombre): bool
    {
        static $cache = [];

        return $cache[$nombre] ??= DB::selectOne(
            'SELECT 1 AS existe FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = ? LIMIT 1',
            [$nombre]
        ) !== null;
    }
}
