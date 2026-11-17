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

    /** Consulta de una tabla limitada a la óptica (solo si la tabla tiene esa columna o un padre que la tenga). */
    protected function de(string $tabla)
    {
        return Alcance::restringir(DB::table($tabla), $tabla, $this->nit());
    }

    protected function citasBase()
    {
        return DB::table('asignacion_cita')
            ->join('usuario', 'usuario.documento', '=', 'asignacion_cita.id_usuario')
            ->join('optometra', 'optometra.doc_optometra', '=', 'asignacion_cita.id_optometra')
            ->join('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado')
            ->leftJoin('tipo_cita', 'tipo_cita.id_tipo_cita', '=', 'asignacion_cita.id_tipo_cita')
            ->where('asignacion_cita.nit_empresa', $this->nit())
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
        return view('staff.recepcion.nueva-cita', [
            'documento' => $request->get('documento'),
            'optometras' => $this->optometras(),
            'tiposCita' => $this->de('tipo_cita')->orderBy('nombre_tipo')->get(),
            'motivos' => $this->de('motivos_cita')->orderBy('nombre_motivo')->get(),
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
            ->where('nit_empresa', $this->nit())->where('id_rol', 2)->first();

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
            ->where('usuario.nit_empresa', $this->nit())->where('usuario.id_rol', 2)
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
    public function solicitudes(): View
    {
        $solicitudes = $this->de('usuarios_no_registrados')
            ->orderByDesc('fecha_registro')->limit(100)->get();

        return view('staff.recepcion.solicitudes', compact('solicitudes'));
    }
}
