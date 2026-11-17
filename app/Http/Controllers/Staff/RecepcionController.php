<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Support\AgendaCitas;
use App\Support\Alcance;
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
 * mensajes de Contáctanos y solicitudes de visitantes. Solo ve los datos de SU óptica.
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
            ->join('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado')
            ->leftJoin('tipo_cita', 'tipo_cita.id_tipo_cita', '=', 'asignacion_cita.id_tipo_cita')
            ->whereRaw($this->esMiNit('asignacion_cita.nit_empresa'), [$this->nitBase()])
            ->select([
                'asignacion_cita.id_cita', 'asignacion_cita.fecha_cita', 'asignacion_cita.hora_cita',
                'asignacion_cita.id_estado', 'asignacion_cita.id_usuario',
                'usuario.nombres', 'usuario.apellido', 'usuario.telefono', 'usuario.email',
                'estado.nom_estado as estado', 'tipo_cita.nombre_tipo as tipo',
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

        $citasHoy = $this->citasBase()->whereDate('asignacion_cita.fecha_cita', $hoy)
            ->orderBy('asignacion_cita.hora_cita')->get();

        $porConfirmar = $this->citasBase()
            ->whereDate('asignacion_cita.fecha_cita', '>=', $hoy)
            ->where('asignacion_cita.id_estado', self::PENDIENTE)->count();

        $solicitudes = AgendaCitas::solicitudesGuardanTurno()
            ? $this->de('usuarios_no_registrados')
                ->where(fn ($q) => $q->whereNull('id_estado')->orWhere('id_estado', self::PENDIENTE))->count()
            : 0;

        return view('staff.recepcion.inicio', [
            'citasHoy' => $citasHoy,
            'porConfirmar' => $porConfirmar,
            'solicitudes' => $solicitudes,
            'mensajes' => $this->mensajesPendientes(),
        ]);
    }

    public function citas(Request $request): View
    {
        $desde = $request->filled('desde') ? $request->date('desde')->toDateString() : now()->toDateString();
        $hasta = $request->filled('hasta') ? $request->date('hasta')->toDateString() : now()->addDays(7)->toDateString();
        $q = trim((string) $request->get('q', ''));

        $citas = $this->citasBase()
            ->whereBetween('asignacion_cita.fecha_cita', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->when($request->filled('estado'), fn ($x) => $x->where('asignacion_cita.id_estado', $request->estado))
            ->when($request->filled('optometra'), fn ($x) => $x->where('asignacion_cita.id_optometra', $request->optometra))
            ->when($q !== '', fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('usuario.documento', 'like', "%{$q}%")
                    ->orWhere('usuario.nombres', 'like', "%{$q}%")
                    ->orWhere('usuario.apellido', 'like', "%{$q}%");
            }))
            ->orderBy('asignacion_cita.fecha_cita')->orderBy('asignacion_cita.hora_cita')
            ->limit(300)->get();

        return view('staff.recepcion.citas', [
            'citas' => $citas,
            'desde' => $desde,
            'hasta' => $hasta,
            'q' => $q,
            'estadoSel' => $request->get('estado'),
            'optometraSel' => $request->get('optometra'),
            'estados' => DB::table('estado')->whereIn('id_estado', [self::PENDIENTE, self::CONFIRMADA, self::COMPLETADO, self::CANCELADO])->orderBy('id_estado')->get(),
            'optometras' => $this->optometras(),
        ]);
    }

    protected function optometras()
    {
        return $this->de('optometra')->where('id_estado', 1)
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
        DB::table('asignacion_cita')->where('id_cita', $id)->update(['id_estado' => $nuevo]);

        return back()->with('success', $request->accion === 'confirmar' ? 'Cita confirmada.' : 'Cita cancelada: el turno quedó libre.');
    }

    public function nuevaCita(Request $request): View
    {
        $motivos = $this->de('motivos_cita')->orderBy('nombre_motivo')->get();

        return view('staff.recepcion.nueva-cita', [
            'documento' => $request->get('documento'),
            'solicitud' => null,
            'optometras' => $this->optometras(),
            'tiposCita' => $this->de('tipo_cita')->orderBy('nombre_tipo')->get(),
            'motivos' => $motivos,
            'fechaMin' => now()->toDateString(),
            'fechaMax' => now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString(),
        ]);
    }

    /** Busca un paciente por documento mientras la recepcionista escribe (la ruta devuelve JSON). */
    public function buscarPaciente(Request $request)
    {
        $doc = trim((string) $request->get('documento', ''));
        if ($doc === '' || ! ctype_digit($doc)) {
            return response()->json(['found' => false]);
        }

        $u = $this->de('usuario')->where('documento', $doc)->where('id_rol', 2)
            ->first(['documento', 'nombres', 'apellido', 'telefono']);

        return response()->json($u ? ['found' => true] + (array) $u : ['found' => false]);
    }

    public function guardarCita(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'documento' => ['required', 'integer'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'id_optometra' => ['required', 'integer', Rule::exists('optometra', 'doc_optometra')],
            'id_tipo_cita' => ['required', 'integer', 'exists:tipo_cita,id_tipo_cita'],
            'id_motivo' => ['required', 'integer', 'exists:motivos_cita,id_motivo'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [
            'hora.required' => 'Escoge uno de los horarios disponibles.',
            'fecha.after_or_equal' => 'La fecha de la cita no puede ser en el pasado.',
        ], ['id_optometra' => 'optómetra', 'id_tipo_cita' => 'tipo de cita', 'id_motivo' => 'motivo']);

        $paciente = DB::table('usuario')->where('documento', $data['documento'])
            ->whereRaw($this->esMiNit('nit_empresa'), [$this->nitBase()])->where('id_rol', 2)->first();

        if (! $paciente) {
            return back()->withInput()->withErrors(['documento' => 'No hay un paciente con ese documento en esta óptica. Regístralo primero.']);
        }

        $fecha = Carbon::createFromFormat('Y-m-d', $data['fecha'])->startOfDay();

        $creada = DB::transaction(function () use ($data, $fecha) {
            // Se bloquea el día del optómetra para que dos personas no tomen el mismo turno a la vez.
            DB::table('asignacion_cita')->whereDate('fecha_cita', $fecha->toDateString())
                ->where('id_optometra', $data['id_optometra'])->lockForUpdate()->get();

            if (! AgendaCitas::turnoDisponible($fecha, $data['hora'], (int) $data['id_optometra'], (int) $data['id_tipo_cita'])) {
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

        if (! $creada) {
            return back()->withInput()->withErrors(['hora' => 'Ese horario ya no está disponible. Escoge otro.']);
        }

        return redirect()->route('recepcion.citas', ['desde' => $data['fecha'], 'hasta' => $data['fecha']])
            ->with('success', "Cita agendada para {$paciente->nombres} el {$fecha->format('d/m/Y')} a las {$data['hora']}.");
    }

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
            ->get(['documento', 'nombres', 'apellido', 'telefono', 'email',
                DB::raw("(SELECT MIN(a.fecha_cita) FROM asignacion_cita a WHERE a.id_usuario = usuario.documento AND a.fecha_cita >= CURDATE() AND a.id_estado <> ".self::CANCELADO.") as proxima")]);

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
        $data = $request->validate([
            'id_tipo_docu' => ['required', 'integer', 'exists:tipo_documento,id_tipo_docu'],
            'documento' => ['required', 'integer', 'min:1', 'unique:usuario,documento'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', 'unique:usuario,telefono'],
            'email' => ['required', 'email', 'max:100', 'unique:usuario,email'],
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

    /** Solicitudes de cita de visitantes sin cuenta (formulario público "Agenda tu cita"). */
    /** Solicitud web (usuarios_no_registrados) de esta óptica, o 404. */
    protected function solicitud(int $id)
    {
        $s = $this->de('usuarios_no_registrados')->where('id_usuario_nr', $id)->first();
        abort_unless($s, 404);

        return $s;
    }

    /** Separa «Juan Carlos Pérez Gómez» en nombres y apellidos (2+2, 1+2 o 1+1 palabras). */
    protected function partirNombre(string $nombre): array
    {
        $p = preg_split('/\s+/', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $n = count($p);
        $corte = $n >= 4 ? 2 : ($n >= 2 ? 1 : $n);

        return [implode(' ', array_slice($p, 0, $corte)), implode(' ', array_slice($p, $corte)) ?: '-'];
    }

    /**
     * Lo que la persona pidió en el sitio. Sale del texto de la solicitud
     * («Motivo — Tipo. Prefiere: 2026-11-20 a las 09:00 con Nombre Optómetra.»),
     * así no hace falta ninguna columna extra en la base.
     */
    protected function pedido(object $s): array
    {
        $t = (string) $s->motivo_cita;
        $r = ['motivo' => trim(Str::before($t, ' — ')), 'tipo' => null, 'fecha' => null, 'hora' => null, 'optometra' => null];

        if (preg_match('/— (.+?)\. Prefiere: (\d{4}-\d{2}-\d{2}) a las (\d{2}:\d{2}) con (.+?)\./u', $t, $m)) {
            [, $r['tipo'], $r['fecha'], $r['hora'], $r['optometra']] = array_map('trim', $m);
        }
        // Si la base sí guarda el turno aparte, esos datos mandan.
        if (! empty($s->fecha_cita)) {
            $r['fecha'] = Carbon::parse($s->fecha_cita)->toDateString();
        }
        if (! empty($s->hora_cita)) {
            $r['hora'] = substr((string) $s->hora_cita, 0, 5);
        }

        return $r;
    }

    public function solicitudes(): View
    {
        $solicitudes = $this->de('usuarios_no_registrados')
            ->orderByDesc('fecha_registro')->limit(100)->get();

        foreach ($solicitudes as $x) {
            $x->pedido = $this->pedido($x);

            // ¿Ya se le agendó la cita? (hay una cita no cancelada de ese documento en esta óptica, hoy o después)
            $x->agendada = ctype_digit((string) $x->documento)
                ? $this->de('asignacion_cita')
                    ->where('id_usuario', (int) $x->documento)->where('id_estado', '!=', self::CANCELADO)
                    ->whereDate('fecha_cita', '>=', now()->toDateString())->orderBy('fecha_cita')->first(['fecha_cita', 'hora_cita'])
                : null;
        }

        return view('staff.recepcion.solicitudes', compact('solicitudes'));
    }

    /** Lo que pidió la persona, traducido a ids de esta óptica (cada dato puede venir null si no se reconoce). */
    protected function interpretar(object $s): array
    {
        $p = $this->pedido($s);
        $igual = fn ($a, $b) => $a !== null && $a !== '' && mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b));

        return [
            'id_motivo' => optional($this->de('motivos_cita')->get()->first(fn ($x) => $igual($x->nombre_motivo, $p['motivo'])))->id_motivo,
            'id_tipo_cita' => optional($this->de('tipo_cita')->get()->first(fn ($x) => $igual($x->nombre_tipo, $p['tipo'])))->id_tipo_cita,
            'id_optometra' => ! empty($s->id_optometra)
                ? (int) $s->id_optometra
                : optional($this->optometras()->first(fn ($o) => $igual($o->nombre, $p['optometra'])))->doc_optometra,
            'fecha' => $p['fecha'],
            'hora' => $p['hora'],
        ];
    }

    /** Formulario de cita con todo lo que la persona pidió ya puesto (para cambiar el turno si hace falta). */
    public function solicitudAgendar(int $id): View
    {
        $s = $this->solicitud($id);
        $i = $this->interpretar($s);

        return view('staff.recepcion.nueva-cita', [
            'documento' => null,
            'solicitud' => $s,
            'motivoSel' => $i['id_motivo'],
            'tipoSel' => $i['id_tipo_cita'],
            'fechaInicial' => ($i['fecha'] && $i['fecha'] >= now()->toDateString()) ? $i['fecha'] : null,
            'horaPref' => $i['hora'],
            'optPref' => $i['id_optometra'],
            'obsInicial' => null,
            'optometras' => $this->optometras(),
            'tiposCita' => $this->de('tipo_cita')->orderBy('nombre_tipo')->get(),
            'motivos' => $this->de('motivos_cita')->orderBy('nombre_motivo')->get(),
            'fechaMin' => now()->toDateString(),
            'fechaMax' => now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString(),
        ]);
    }

    /**
     * Un clic: registra la cita tal como la pidió la persona (día, hora, optómetra, tipo y motivo).
     * Si falta algún dato o el turno ya no está libre, lleva al formulario para escoger otro.
     */
    public function solicitudRegistrar(int $id): RedirectResponse
    {
        $s = $this->solicitud($id);
        $i = $this->interpretar($s);

        $faltan = array_keys(array_filter($i, fn ($v) => $v === null || $v === ''));
        if ($faltan) {
            return redirect()->route('recepcion.solicitud.agendar', $id)
                ->with('aviso', 'No se pudo leer todo lo que pidió la persona (' . implode(', ', array_map(fn ($k) => str_replace(['id_', '_cita'], ['', ''], $k), $faltan)) . '). Completa los datos y confirma.');
        }
        if ($i['fecha'] < now()->toDateString()) {
            return redirect()->route('recepcion.solicitud.agendar', $id)
                ->with('aviso', 'La fecha que pidió ya pasó. Escoge un nuevo día y horario.');
        }

        $error = $this->crearCitaDeSolicitud($s, [
            'fecha' => $i['fecha'], 'hora' => $i['hora'], 'id_optometra' => $i['id_optometra'],
            'id_tipo_cita' => $i['id_tipo_cita'], 'id_motivo' => $i['id_motivo'], 'observaciones' => null,
        ]);

        if ($error) {
            return redirect()->route('recepcion.solicitud.agendar', $id)->with('aviso', $error);
        }

        return redirect()->route('recepcion.solicitudes')
            ->with('success', "Cita de {$s->nombre} registrada para el " . Carbon::parse($i['fecha'])->format('d/m/Y') . " a las {$i['hora']}. Ya la ve el optómetra y el turno quedó ocupado.");
    }

    /**
     * Agenda de verdad la cita (asignacion_cita): desde ahí la ve el optómetra y el turno queda
     * ocupado para quien quiera agendar en el sitio. La tabla de citas exige un usuario (llave
     * foránea): si la persona no tiene cuenta se crea una básica con los datos de la solicitud y
     * una contraseña aleatoria; ella la activa luego con «¿Olvidaste tu contraseña?» y ese correo.
     * Devuelve null si todo salió bien, o el mensaje del problema.
     */
    protected function crearCitaDeSolicitud(object $s, array $data): ?string
    {
        if (! ctype_digit((string) $s->documento)) {
            return 'El documento de la solicitud no es válido para crear la cita.';
        }
        $documento = (int) $s->documento;
        $fecha = Carbon::createFromFormat('Y-m-d', $data['fecha'])->startOfDay();

        $usuario = DB::table('usuario')->where('documento', $documento)->first();
        if ($usuario && ((int) $usuario->id_rol !== 2 || substr(preg_replace('/\\D/', '', (string) $usuario->nit_empresa), 0, 9) !== $this->nitBase())) {
            return 'Ese documento pertenece a otra cuenta que no es un paciente de esta óptica. Revísalo con el administrador.';
        }
        if (! $usuario && (DB::table('usuario')->where('email', $s->correo)->exists() || DB::table('usuario')->where('telefono', $s->telefono)->exists())) {
            return 'El correo o el celular de la solicitud ya pertenecen a otra cuenta con distinto documento. Confírmalo con la persona antes de agendar.';
        }

        try {
            DB::transaction(function () use ($data, $fecha, $s, $documento, $usuario) {
                DB::table('asignacion_cita')->whereDate('fecha_cita', $fecha->toDateString())
                    ->where('id_optometra', $data['id_optometra'])->lockForUpdate()->get();

                // Si la base aparta el turno en la propia solicitud, se suelta para que ella no se bloquee a sí misma.
                if (AgendaCitas::solicitudesGuardanTurno()) {
                    DB::table('usuarios_no_registrados')->where('id_usuario_nr', $s->id_usuario_nr)->update(['id_estado' => self::CONFIRMADA]);
                }

                if (! AgendaCitas::turnoDisponible($fecha, $data['hora'], (int) $data['id_optometra'], (int) $data['id_tipo_cita'])) {
                    throw new \RuntimeException('turno-ocupado'); // revierte lo anterior
                }

                if (! $usuario) {
                    [$nombres, $apellido] = $this->partirNombre((string) $s->nombre);
                    DB::table('usuario')->insert([
                        'documento' => $documento,
                        'nombres' => $nombres,
                        'apellido' => $apellido,
                        'telefono' => $s->telefono,
                        'email' => $s->correo,
                        'password' => Hash::make(Str::random(24)),
                        'fecha_creacion' => now(),
                        'id_rol' => 2,
                        'id_tipo_docu' => $s->id_tipo_docu ?? 1,
                        'id_estado' => 1,
                        'nit_empresa' => $this->nit(),
                    ]);
                }

                $nota = 'Solicitud web: ' . trim(Str::before((string) $s->motivo_cita, ' — '));
                if (! empty($data['observaciones'])) {
                    $nota .= '. ' . $data['observaciones'];
                }

                DB::table('asignacion_cita')->insert([
                    'fecha_cita' => $fecha->toDateString(),
                    'hora_cita' => $data['hora'] . ':00',
                    'observaciones' => Str::limit($nota, 500, ''),
                    'id_usuario' => $documento,
                    'id_optometra' => $data['id_optometra'],
                    'id_tipo_cita' => $data['id_tipo_cita'],
                    'id_estado' => self::CONFIRMADA,
                    'id_motivo' => $data['id_motivo'],
                    'nit_empresa' => $this->nit(),
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'turno-ocupado') {
                throw $e;
            }

            return 'Ese horario ya no está disponible. Escoge otro.';
        }

        return null;
    }

    public function solicitudGuardar(Request $request, int $id): RedirectResponse
    {
        $s = $this->solicitud($id);

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'id_optometra' => ['required', 'integer', Rule::exists('optometra', 'doc_optometra')],
            'id_tipo_cita' => ['required', 'integer', 'exists:tipo_cita,id_tipo_cita'],
            'id_motivo' => ['required', 'integer', 'exists:motivos_cita,id_motivo'],
            'observaciones' => ['nullable', 'string', 'max:300'],
        ], [
            'hora.required' => 'Escoge uno de los horarios disponibles.',
            'fecha.after_or_equal' => 'La fecha de la cita no puede ser en el pasado.',
        ], ['id_optometra' => 'optómetra', 'id_tipo_cita' => 'tipo de cita', 'id_motivo' => 'motivo']);

        if ($error = $this->crearCitaDeSolicitud($s, $data)) {
            return back()->withInput()->withErrors(['solicitud' => $error]);
        }

        $f = Carbon::createFromFormat('Y-m-d', $data['fecha']);

        return redirect()->route('recepcion.solicitudes')
            ->with('success', "Cita de {$s->nombre} registrada para el {$f->format('d/m/Y')} a las {$data['hora']}. Ya la ve el optómetra y el turno quedó ocupado.");
    }
}
