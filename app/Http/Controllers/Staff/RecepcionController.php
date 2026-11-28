<?php

namespace App\Http\Controllers\Staff;

use App\Support\ApisExternas;
use App\Http\Controllers\Controller;
use App\Support\AgendaCitas;
use App\Support\Alcance;
use App\Support\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Panel de la recepcionista (/recepcion): agenda de toda la óptica, citas nuevas, pacientes,
 * mensajes de Contáctanos. Solo ve los datos de SU óptica.
 */
class RecepcionController extends Controller
{
    protected const PENDIENTE = 3;
    protected const CANCELADO = 4;
    protected const COMPLETADO = 5;
    protected const CONFIRMADA = 11;

    protected function nit()
    {
        return Auth::user()->nit_empresa;
    }

    /**
     * El NIT se guarda de varias formas según la tabla y la versión del sitio ('900000000', '900000000-1', 9000000001).
     * Para reconocer la misma óptica se comparan solo los 9 primeros dígitos (el último es el dígito de verificación).
     */
    protected function nitBase(): string
    {
        return substr(preg_replace('/\D/', '', (string) $this->nit()), 0, 9);
    }

    /** Condición SQL: la columna $col es de la óptica de la recepcionista (se usa con whereRaw y [$this->nitBase()]). */
    protected function esMiNit(string $col): string
    {
        return "SUBSTR(REPLACE(REPLACE(REPLACE(CAST({$col} AS CHAR), '-', ''), '.', ''), ' ', ''), 1, 9) = ?";
    }

    /** Consulta de una tabla limitada a la óptica (solo si la tabla tiene esa columna o un padre que la tenga). */
    protected function de(string $tabla)
    {
        $q = DB::table($tabla);

        if (Alcance::tieneNit($tabla)) {
            return $q->whereRaw($this->esMiNit("{$tabla}.nit_empresa"), [$this->nitBase()]);
        }

        return Alcance::restringir($q, $tabla, $this->nit());
    }

    protected function citasBase()
    {
        return DB::table('asignacion_cita')
            ->join('usuario', 'usuario.documento', '=', 'asignacion_cita.id_usuario')
            ->join('optometra', 'optometra.doc_optometra', '=', 'asignacion_cita.id_optometra')
            // leftJoin: una cita no desaparece de la lista aunque su estado no esté en la tabla `estado`
            ->leftJoin('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado')
            ->leftJoin('tipo_cita', 'tipo_cita.id_tipo_cita', '=', 'asignacion_cita.id_tipo_cita')
            ->leftJoin('motivos_cita', 'motivos_cita.id_motivo', '=', 'asignacion_cita.id_motivo')
            ->whereRaw($this->esMiNit('asignacion_cita.nit_empresa'), [$this->nitBase()])
            ->select([
                'asignacion_cita.id_cita', 'asignacion_cita.fecha_cita', 'asignacion_cita.hora_cita',
                'asignacion_cita.id_estado', 'asignacion_cita.id_usuario', 'asignacion_cita.observaciones',
                'usuario.documento', 'usuario.nombres', 'usuario.apellido', 'usuario.telefono', 'usuario.email',
                DB::raw("COALESCE(estado.nom_estado, CASE asignacion_cita.id_estado WHEN 3 THEN 'Pendiente' WHEN 4 THEN 'Cancelado' WHEN 5 THEN 'Completado' WHEN 11 THEN 'Confirmada' ELSE 'Sin estado' END) as estado"),
                'tipo_cita.nombre_tipo as tipo', 'motivos_cita.nombre_motivo as motivo',
                DB::raw("CONCAT(optometra.nombre, ' ', optometra.apellido) as optometra"),
            ]);
    }

    protected function mensajesPendientes(): int
    {
        if (! Schema::hasColumn('mensajes_contacto', 'estado_contactenos')) {
            return 0;
        }

        return $this->de('mensajes_contacto')
            ->where('estado_contactenos', 'Por contactar')->count();
    }

    public function inicio(): View
    {
        $hoy = now()->toDateString();

        // Las citas se listan de la más reciente a la más antigua.
        $citasHoy = $this->citasBase()->whereDate('asignacion_cita.fecha_cita', $hoy)
            ->orderByDesc('asignacion_cita.hora_cita')->get();

        $porConfirmar = $this->citasBase()
            ->whereDate('asignacion_cita.fecha_cita', '>=', $hoy)
            ->where('asignacion_cita.id_estado', self::PENDIENTE)->count();

        return view('staff.recepcion.inicio', [
            'citasHoy' => $citasHoy,
            'porConfirmar' => $porConfirmar,
            'mensajes' => $this->mensajesPendientes(),
        ]);
    }

    /** Rango de fechas del filtro: por defecto desde hace 30 días hasta el límite en que se puede reservar. */
    protected function rango(Request $request): array
    {
        $desde = $request->filled('desde') ? $request->date('desde')->toDateString() : now()->subDays(30)->toDateString();
        $hasta = $request->filled('hasta') ? $request->date('hasta')->toDateString() : now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString();

        return [$desde, $hasta];
    }

    /**
     * Citas de la óptica con los filtros del formulario. El documento y el nombre se aplican JUNTOS:
     * si se escriben los dos, la cita debe cumplir ambos. Orden: de la más reciente a la más antigua.
     */
    protected function citasFiltradas(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);
        $documento = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->get('documento', '')));
        $nombre = trim((string) $request->get('nombre', ''));

        return $this->citasBase()
            ->whereBetween('asignacion_cita.fecha_cita', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->when($request->filled('estado'), fn ($x) => $x->where('asignacion_cita.id_estado', $request->estado))
            ->when($request->filled('optometra'), fn ($x) => $x->where('asignacion_cita.id_optometra', $request->optometra))
            ->when($documento !== '', fn ($x) => $x->where('usuario.documento', 'like', "%{$documento}%"))
            ->when($nombre !== '', function ($x) use ($nombre) {
                // Cada palabra escrita debe aparecer en el nombre o en el apellido ("ana mar" encuentra "Ana Marcela").
                foreach (preg_split('/\s+/', $nombre, -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
                    $x->where(fn ($w) => $w->where('usuario.nombres', 'like', "%{$palabra}%")->orWhere('usuario.apellido', 'like', "%{$palabra}%"));
                }
            })
            ->orderByDesc('asignacion_cita.fecha_cita')->orderByDesc('asignacion_cita.hora_cita');
    }

    public function citas(Request $request): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('staff.recepcion.citas', [
            'citas' => $this->citasFiltradas($request)->limit(300)->get(),
            'desde' => $desde,
            'hasta' => $hasta,
            'documento' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->get('documento', ''))),
            'nombre' => trim((string) $request->get('nombre', '')),
            'estadoSel' => $request->get('estado'),
            'optometraSel' => $request->get('optometra'),
            // Estados de una cita (fijos: no dependen de que la tabla `estado` traiga el 11 «Confirmada»).
            'estados' => collect([self::PENDIENTE => 'Pendiente', self::CONFIRMADA => 'Confirmada', self::COMPLETADO => 'Completado', self::CANCELADO => 'Cancelado']),
            'optometras' => $this->optometras(),
        ]);
    }

    /** Descarga en Excel (.xlsx) las citas que muestra la lista con los mismos filtros. */
    public function exportarCitas(Request $request)
    {
        $headers = ['Fecha', 'Hora', 'Documento', 'Paciente', 'Teléfono', 'Correo', 'Optómetra', 'Tipo de cita', 'Motivo', 'Estado', 'Observaciones'];
        $filas = $this->citasFiltradas($request)->limit(5000)->get()->map(fn ($c) => [
            Carbon::parse($c->fecha_cita)->format('d/m/Y'),
            substr((string) $c->hora_cita, 0, 5),
            (string) $c->documento,
            trim($c->nombres.' '.$c->apellido),
            (string) $c->telefono,
            (string) $c->email,
            (string) $c->optometra,
            (string) $c->tipo,
            (string) $c->motivo,
            (string) $c->estado,
            (string) $c->observaciones,
        ])->all();

        $nombre = 'citas_'.now()->format('Y-m-d_His');

        try {
            $ruta = SimpleXlsx::build('Citas', $headers, $filas);

            return response()->download($ruta, $nombre.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            // Sin la extensión zip del servidor: un CSV que Excel abre igual.
            return response()->streamDownload(function () use ($headers, $filas) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers, ';');
                foreach ($filas as $fila) {
                    fputcsv($out, $fila, ';');
                }
                fclose($out);
            }, $nombre.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
    }

    protected function optometras()
    {
        return $this->de('optometra')->whereIn('id_estado', [1, 3])
            ->orderBy('nombre')->get(['doc_optometra', DB::raw("CONCAT(nombre, ' ', apellido) as nombre")]);
    }

    /** Confirmar o cancelar una cita. Las canceladas liberan el turno; las completadas ya no se tocan. */
    public function estadoCita(Request $request, int $id): RedirectResponse
    {
        $request->validate(['accion' => ['required', 'in:confirmar,cancelar']]);

        $cita = $this->de('asignacion_cita')->where('id_cita', $id)->first();
        abort_unless($cita, 404);

        if ($cita->id_estado == self::COMPLETADO) {
            return back()->withErrors(['general' => 'Esa cita ya fue atendida y no se puede modificar.']);
        }

        $nuevo = $request->accion === 'confirmar' ? self::CONFIRMADA : self::CANCELADO;

        try {
            DB::table('asignacion_cita')->where('id_cita', $id)->update(['id_estado' => $nuevo]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Confirmar una cita cancelada la reactiva: el trigger la rechaza si el turno ya lo tomó otra.
            return back()->withErrors(['general' => \App\Http\Controllers\Optometra\PanelController::mensajeBd($e)]);
        }

        return back()->with('success', $request->accion === 'confirmar' ? 'Cita confirmada.' : 'Cita cancelada: el turno quedó libre.');
    }

    public function nuevaCita(Request $request): View
    {
        $motivos = $this->de('motivos_cita')->orderBy('nombre_motivo')->get();

        return view('staff.recepcion.nueva-cita', [
            'documento' => $request->get('documento'),
            'optometras' => $this->optometras(),
            'tiposCita' => $this->de('tipo_cita')->orderBy('nombre_tipo')->get()->keyBy('id_tipo_cita'),
            // El tipo de cita sale del motivo: [id_motivo => id_tipo_cita].
            'tipoPorMotivo' => AgendaCitas::mapaTipoPorMotivo(),
            'motivos' => $motivos,
            'fechaMin' => now()->toDateString(),
            'fechaMax' => now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString(),
            // Festivos del rango del calendario (se pintan de amarillo y no se pueden escoger). null si la API no respondió.
            'festivos' => ApisExternas::festivosEntre(now()->toDateString(), now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString()),
        ]);
    }

    /** Busca un paciente por documento mientras la recepcionista escribe (la ruta devuelve JSON). */
    public function buscarPaciente(Request $request)
    {
        $doc = strtoupper(trim((string) $request->get('documento', '')));
        if ($doc === '' || ! ctype_alnum($doc)) {
            return response()->json(['found' => false]);
        }

        $u = $this->de('usuario')->where('documento', $doc)->where('id_rol', 2)
            ->first(['documento', 'nombres', 'apellido', 'telefono']);

        return response()->json($u ? ['found' => true] + (array) $u : ['found' => false]);
    }

    public function guardarCita(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'documento' => ['required', 'string', 'max:20'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            // Solo optómetras habilitados: los mismos que muestra la agenda (1 = activo, 3 = cuenta nueva).
            'id_optometra' => ['required', 'string', 'max:20', Rule::exists('optometra', 'doc_optometra')->whereIn('id_estado', [1, 3])],
            'id_motivo' => ['required', 'integer', 'exists:motivos_cita,id_motivo'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [
            'hora.required' => 'Escoge uno de los horarios disponibles.',
            'id_optometra.exists' => 'Ese optómetra no está habilitado. Escoge otro horario.',
            'fecha.after_or_equal' => 'La fecha de la cita no puede ser en el pasado.',
        ], ['id_optometra' => 'optómetra', 'id_motivo' => 'motivo']);

        // El tipo de cita lo decide el motivo: no se recibe aparte.
        $data['id_tipo_cita'] = AgendaCitas::tipoParaMotivo((int) $data['id_motivo']);
        if (! $data['id_tipo_cita']) {
            return back()->withInput()->withErrors(['id_motivo' => 'No hay un tipo de cita para ese motivo. Escoge otro.']);
        }

        $paciente = DB::table('usuario')->where('documento', $data['documento'])
            ->whereRaw($this->esMiNit('nit_empresa'), [$this->nitBase()])->where('id_rol', 2)->first();

        if (! $paciente) {
            return back()->withInput()->withErrors(['documento' => 'No hay un paciente con ese documento en esta óptica. Regístralo primero.']);
        }

        $fecha = Carbon::createFromFormat('Y-m-d', $data['fecha'])->startOfDay();

        // Antes de guardar se consulta la API de festivos de Colombia (date.nager.at): los festivos no se atienden.
        $festivo = ApisExternas::esFestivo($data['fecha']);
        if ($festivo && $festivo['festivo']) {
            return back()->withInput()
                ->withErrors(['fecha' => "El {$fecha->format('d/m/Y')} es festivo ({$festivo['nombre']}) y la óptica no atiende. Escoge otro día."]);
        }

        try {
        $creada = DB::transaction(function () use ($data, $fecha) {
            // Se bloquea el día del optómetra para que dos personas no tomen el mismo turno a la vez.
            DB::table('asignacion_cita')->whereDate('fecha_cita', $fecha->toDateString())
                ->where('id_optometra', $data['id_optometra'])->lockForUpdate()->get();

            if (! AgendaCitas::turnoDisponible($fecha, $data['hora'], (string) $data['id_optometra'], (int) $data['id_tipo_cita'], (string) $this->nit())) {
                return false;
            }

            DB::table('asignacion_cita')->insert([
                'fecha_cita' => $fecha->toDateString(),
                'hora_cita' => $data['hora'].':00',
                'observaciones' => $data['observaciones'] ?? null,
                'id_usuario' => $data['documento'],
                'id_optometra' => $data['id_optometra'],
                'id_tipo_cita' => $data['id_tipo_cita'],
                'id_estado' => self::CONFIRMADA, // la recepcionista la agenda en persona o por teléfono: nace confirmada
                'id_motivo' => $data['id_motivo'],
                'nit_empresa' => $this->nit(),
            ]);

            return true;
        });
        } catch (\Illuminate\Database\QueryException $e) {
            // El trigger de la base rechaza la doble reserva (SQLSTATE 45000) si dos personas toman el turno a la vez.
            if (($e->errorInfo[0] ?? null) !== '45000') {
                throw $e;
            }
            $creada = false;
        }

        if (! $creada) {
            return back()->withInput()->withErrors(['hora' => 'Ese horario ya no está disponible. Escoge otro.']);
        }

        return redirect()->route('recepcion.citas', ['desde' => $data['fecha'], 'hasta' => $data['fecha']])
            ->with('success', "Cita agendada para {$paciente->nombres} el {$fecha->format('d/m/Y')} a las {$data['hora']}."
                . ($festivo === null ? ' Ojo: no se pudo consultar la API de festivos, revisa que ese día no sea festivo.' : ''));
    }

    /** Pacientes de la óptica, cada uno con sus citas (la más reciente primero) en la misma fila. */
    public function pacientes(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $pacientes = DB::table('usuario')
            ->whereRaw($this->esMiNit('usuario.nit_empresa'), [$this->nitBase()])->where('usuario.id_rol', 2)
            ->when($q !== '', fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('documento', 'like', "%{$q}%")->orWhere('nombres', 'like', "%{$q}%")
                    ->orWhere('apellido', 'like', "%{$q}%")->orWhere('telefono', 'like', "%{$q}%");
            }))
            ->orderBy('nombres')->limit(100)
            ->get(['documento', 'nombres', 'apellido', 'telefono', 'email']);

        // Todas las citas de esos pacientes en una sola consulta, de la más reciente a la más antigua.
        $citas = $pacientes->isEmpty() ? collect() : $this->citasBase()
            ->whereIn('asignacion_cita.id_usuario', $pacientes->pluck('documento')->all())
            ->orderByDesc('asignacion_cita.fecha_cita')->orderByDesc('asignacion_cita.hora_cita')
            ->get()->groupBy('id_usuario');

        $hoy = now()->toDateString();
        foreach ($pacientes as $p) {
            $p->citas = $citas->get($p->documento, collect());
            // Próxima cita: la más cercana desde hoy que no esté cancelada.
            $p->proxima = $p->citas
                ->filter(fn ($c) => substr((string) $c->fecha_cita, 0, 10) >= $hoy && (int) $c->id_estado !== self::CANCELADO)
                ->sortBy(fn ($c) => $c->fecha_cita.' '.$c->hora_cita)->first();
        }

        return view('staff.recepcion.pacientes', compact('pacientes', 'q'));
    }

    public function nuevoPaciente(): View
    {
        return view('staff.recepcion.nuevo-paciente', [
            'tiposDoc' => DB::table('tipo_documento')->orderBy('id_tipo_docu')->get(),
        ]);
    }

    public function guardarPaciente(Request $request): RedirectResponse
    {
        $request->merge(['documento' => \App\Support\TipoDocumento::normalizar($request->input('documento'))]);

        $data = $request->validate([
            'id_tipo_docu' => ['required', 'integer', 'exists:tipo_documento,id_tipo_docu'],
            'documento' => ['required', 'string', 'max:20', new \App\Rules\DocumentoValido($request->input('id_tipo_docu')), 'unique:usuario,documento', 'unique:optometra,doc_optometra'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', 'unique:usuario,telefono'],
            'email' => ['required', 'email', 'max:100', 'unique:usuario,email', 'unique:optometra,correo'],
        ], [
            'documento.unique' => 'Ya existe un usuario con ese documento.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'telefono.unique' => 'Ya existe un usuario con ese celular.',
        ], ['id_tipo_docu' => 'tipo de documento', 'telefono' => 'celular', 'email' => 'correo']);

        // Queda como cliente activo. La contraseña es aleatoria: el paciente la crea después
        // con "¿Olvidaste tu contraseña?" en el inicio de sesión (a su correo).
        DB::table('usuario')->insert([
            'documento' => $data['documento'],
            'nombres' => $data['nombres'],
            'apellido' => $data['apellido'],
            'telefono' => $data['telefono'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(24)),
            'fecha_creacion' => now(),
            'id_rol' => 2,
            'id_tipo_docu' => $data['id_tipo_docu'],
            'id_estado' => 1,
            'nit_empresa' => $this->nit(),
        ]);

        return redirect()->route('recepcion.cita.nueva', ['documento' => $data['documento']])
            ->with('success', "Paciente {$data['nombres']} registrado. Ya puedes agendarle su cita.");
    }

    public function contacto(): View
    {
        $mensajes = Schema::hasColumn('mensajes_contacto', 'estado_contactenos')
            ? $this->de('mensajes_contacto')
                ->orderBy('estado_contactenos')->orderByDesc('fecha_contactenos')->limit(100)->get()
            : collect();

        return view('staff.recepcion.contacto', compact('mensajes'));
    }

    public function contactoEstado(int $id): RedirectResponse
    {
        $m = $this->de('mensajes_contacto')->where('id_contactenos', $id)->first();
        abort_unless($m, 404);

        $nuevo = $m->estado_contactenos === 'Contactado' ? 'Por contactar' : 'Contactado';
        DB::table('mensajes_contacto')->where('id_contactenos', $id)->update(['estado_contactenos' => $nuevo]);

        return back()->with('success', $nuevo === 'Contactado' ? "Marcaste a {$m->nombre_contactenos} como contactado." : "{$m->nombre_contactenos} volvió a «Por contactar».");
    }
}
