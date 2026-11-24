<?php

namespace App\Http\Controllers;

use App\Support\ApisExternas;
use App\Models\Empresa;
use App\Models\PaginaEmpresa;
use App\Support\AgendaCitas;
use App\Support\Licencias;
use App\Support\Nit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    /**
     * Página principal ("/"): muestra la óptica configurada como principal.
     */
    public function inicio()
    {
        $empresa = Empresa::principal();
        abort_unless($empresa, 404, 'No hay ninguna óptica registrada. Ejecuta los scripts SQL de database/sql y vuelve a intentar.');

        return $this->mostrarPagina($empresa);
    }

    /**
     * Página pública de una óptica: /optica/{slug}
     */
    public function optica(string $slug)
    {
        $empresa = Empresa::where('slug', $slug)->firstOrFail();

        return $this->mostrarPagina($empresa);
    }

    /**
     * Pinta el index con el contenido de la óptica. Si está suspendida o sin
     * licencia vigente, muestra un aviso en lugar de la página.
     * También recuerda en la sesión qué óptica está viendo el visitante, para que
     * las citas que agende queden a nombre de esa óptica.
     */
    protected function mostrarPagina(Empresa $empresa)
    {
        if (! Licencias::puedeIngresar($empresa)) {
            return response()->view('optica-no-disponible', ['empresa' => $empresa], 503);
        }

        session(['optica_nit' => $empresa->nit]);

        return view('index', [
            'empresa' => $empresa,
            'pagina' => PaginaEmpresa::deEmpresa($empresa),
            // Índice UV actual de Ibagué para la sección de rayos UV (null si la API no respondió).
            'uv' => ApisExternas::uvActual(),
            'pageTitle' => $empresa->nombre . ' ·',
        ]);
    }

    /**
     * Vista pública: Terapia Visual (niños y adultos).
     */
    public function terapiaVisual()
    {
        return view('terapia-visual');
    }
    /**
     * Vista pública: Conócenos (misión, visión, mapa y formulario de contacto).
     */
    public function conocenos()
    {
        return view('conocenos');
    }

    /**
     * Vista pública: Planes (Free, Básico, Profesional, Empresarial).
     */
    // GET /planes: página pública con los planes a la venta.
    public function planes()
    {
        // Los precios salen de la tabla `plan`, que edita el superadmin.
        // Planes activos ordenados por duración; los precios los edita el superadmin en la tabla plan.
        $planes = \App\Models\Plan::where('activo', 1)->orderBy('meses')->get();

        // Muestra resources/views/planes.blade.php.
        return view('planes', compact('planes'));
    }

    /**
     * Procesa el formulario de contacto de la vista "Conócenos".
     *
     * El mensaje se guarda en la tabla `mensajes_contacto` con el NIT de la
     * óptica que se estaba viendo, y aparece en el panel del administrador
     * en "Por contactar" (CRUD genérico, config/admin_tables.php).
     */
    public function enviarContacto(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            // La columna correo_contactenos es VARCHAR(50): un correo más largo no cabría en la base.
            'correo' => ['required', 'email', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ], [
            'correo.max' => 'El correo no puede tener más de 50 caracteres.',
            'mensaje.required' => 'Escribe tu mensaje.',
        ]);

        // Se guarda a nombre de la óptica cuya página estaba viendo el visitante
        // (la recuerda mostrarPagina() en la sesión). El administrador de esa óptica
        // lo ve en su panel, en "Por contactar".
        if (Schema::hasTable('mensajes_contacto')) {
            $mensaje = [
                'nombre_contactenos' => $request->input('nombre'),
                'correo_contactenos' => $request->input('correo'),
                'telefono_contactenos' => $request->input('telefono'),
                'mensaje_contactenos' => $request->input('mensaje'),
            ];

            // Columnas del script database/sql/2026_10_19_mensajes_contacto.sql (si ya se ejecutó)
            if (Schema::hasColumn('mensajes_contacto', 'nit_empresa')) {
                // Óptica que se estaba viendo; si no se visitó su página, la principal. El NIT se adapta al tipo de columna
                // (un NIT de 10 dígitos no cabe en una columna INT).
                $nit = session('optica_nit') ?? Empresa::principal()?->getRawOriginal('nit') ?? config('visioptica.empresa_principal');
                $mensaje['nit_empresa'] = Nit::paraColumna('mensajes_contacto', 'nit_empresa', $nit);
            }
            if (Schema::hasColumn('mensajes_contacto', 'estado_contactenos')) {
                $mensaje['estado_contactenos'] = 'Por contactar';
            }
            if (Schema::hasColumn('mensajes_contacto', 'fecha_contactenos')) {
                $mensaje['fecha_contactenos'] = now();
            }

            DB::table('mensajes_contacto')->insert($mensaje);
        }

        return redirect()->route('conocenos')->with('contacto_enviado', true);
    }

    /**
     * Vista pública: agendar una cita.
     *
     * La persona escoge el motivo (el tipo de cita sale del motivo), el día y uno de los
     * turnos libres de los optómetras habilitados. Para reservar hay que tener cuenta:
     * si no ha iniciado sesión, al enviar el formulario se le pide entrar o registrarse
     * y, al volver, el formulario aparece con lo que ya había escogido.
     */
    public function agendarCita(Request $request)
    {
        $usuario = Auth::user();

        // Datos que la persona ya había escogido antes de ir a iniciar sesión o registrarse.
        if ($usuario && ($pendiente = $request->session()->pull('cita_pendiente'))) {
            $request->session()->flashInput($pendiente);
            $request->session()->flash('cita_recuperada', true);
        }

        $proximasCitas = collect();
        if ($usuario) {
            $proximasCitas = DB::table('asignacion_cita')
                ->join('optometra', 'optometra.doc_optometra', '=', 'asignacion_cita.id_optometra')
                ->join('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado')
                ->leftJoin('tipo_cita', 'tipo_cita.id_tipo_cita', '=', 'asignacion_cita.id_tipo_cita')
                ->where('asignacion_cita.id_usuario', $usuario->documento)
                ->whereDate('asignacion_cita.fecha_cita', '>=', now()->toDateString())
                ->orderBy('asignacion_cita.fecha_cita')
                ->orderBy('asignacion_cita.hora_cita')
                ->get([
                    'asignacion_cita.fecha_cita', 'asignacion_cita.hora_cita', 'estado.nom_estado',
                    'tipo_cita.nombre_tipo',
                    DB::raw("CONCAT(optometra.nombre, ' ', optometra.apellido) as optometra"),
                ]);
        }

        $tipos = DB::table('tipo_cita')->orderBy('nombre_tipo')->get()->keyBy('id_tipo_cita');

        return view('agendar-cita', [
            'motivos' => DB::table('motivos_cita')->orderBy('nombre_motivo')->get(),
            'tiposCita' => $tipos,
            'tipoPorMotivo' => AgendaCitas::mapaTipoPorMotivo(),
            'diasAtencion' => AgendaCitas::diasConAtencion(),
            'usuario' => $usuario,
            'proximasCitas' => $proximasCitas,
            'fechaMin' => now()->toDateString(),
            'fechaMax' => now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString(),
            // Festivos del rango del calendario (se pintan de amarillo y no se pueden escoger). null si la API no respondió.
            'festivos' => ApisExternas::festivosEntre(now()->toDateString(), now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString()),
        ]);
    }

    /**
     * Turnos libres de un día (lo consulta el formulario al escoger la fecha).
     */
    public function horariosDisponibles(Request $request): JsonResponse
    {
        $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'id_tipo_cita' => ['nullable', 'integer'],
        ]);

        $fecha = Carbon::createFromFormat('Y-m-d', $request->fecha)->startOfDay();

        return response()->json([
            'dia' => AgendaCitas::nombreDia($fecha),
            'turnos' => AgendaCitas::turnosLibres($fecha, $request->filled('id_tipo_cita') ? (int) $request->id_tipo_cita : null),
            'minutos' => AgendaCitas::minutosDelTipo($request->filled('id_tipo_cita') ? (int) $request->id_tipo_cita : null),
        ]);
    }

    /**
     * Guarda la cita. Sin sesión no se guarda nada: se recuerda lo escogido, se manda a
     * iniciar sesión (o a registrarse) y después se vuelve a este formulario.
     */
    public function guardarCita(Request $request)
    {
        $usuario = Auth::user();

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            // Solo se pueden escoger optómetras habilitados (activos).
            'id_optometra' => ['required', 'integer', Rule::exists('optometra', 'doc_optometra')->where('id_estado', 1)],
            'id_motivo' => ['required', 'integer', 'exists:motivos_cita,id_motivo'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ], [
            'hora.required' => 'Escoge uno de los horarios disponibles.',
            'id_optometra.required' => 'Escoge uno de los horarios disponibles.',
            'id_optometra.exists' => 'Ese optómetra no está disponible. Escoge otro horario.',
            'fecha.after_or_equal' => 'La fecha de la cita no puede ser en el pasado.',
        ], [
            'fecha' => 'fecha de la cita',
            'hora' => 'hora de la cita',
            'id_optometra' => 'optómetra',
            'id_motivo' => 'motivo de la cita',
        ]);

        // El tipo de cita lo decide el motivo; no se recibe aparte.
        $idTipo = AgendaCitas::tipoParaMotivo((int) $data['id_motivo']);
        if (! $idTipo) {
            return back()->withInput()->withErrors(['id_motivo' => 'No hay un tipo de cita para ese motivo. Escoge otro.']);
        }

        // Sin sesión: se guarda lo escogido y se manda a iniciar sesión o registrarse.
        if (! $usuario) {
            $request->session()->put('cita_pendiente', $request->only(['fecha', 'hora', 'id_optometra', 'id_motivo', 'comentario']));
            $request->session()->put('url.intended', route('citas.create'));

            return redirect()->route('login')
                ->with('info', 'Inicia sesión para reservar tu cita. Si aún no tienes cuenta, regístrate: tu cita te espera.');
        }

        $fecha = Carbon::createFromFormat('Y-m-d', $data['fecha'])->startOfDay();

        // Los festivos no se atienden (API de festivos de Colombia).
        $festivo = ApisExternas::esFestivo($data['fecha']);
        if ($festivo && $festivo['festivo']) {
            return back()->withInput()
                ->withErrors(['fecha' => "El {$fecha->format('d/m/Y')} es festivo ({$festivo['nombre']}) y la óptica no atiende. Escoge otro día."]);
        }

        $motivo = DB::table('motivos_cita')->where('id_motivo', $data['id_motivo'])->value('nombre_motivo');
        $tipo = DB::table('tipo_cita')->where('id_tipo_cita', $idTipo)->value('nombre_tipo');
        $optometra = DB::table('optometra')->where('doc_optometra', $data['id_optometra'])
            ->selectRaw("CONCAT(nombre, ' ', apellido) as n")->value('n');

        $resumen = [
            'fecha' => ucfirst($fecha->locale('es')->translatedFormat('l j \d\e F \d\e Y')),
            'hora' => $data['hora'],
            'optometra' => $optometra,
            'tipo' => $tipo,
            'motivo' => $motivo,
        ];

        // Se vuelve a revisar el turno dentro de una transacción para
        // que dos personas no reserven la misma hora al mismo tiempo.
        $reservada = DB::transaction(function () use ($data, $fecha, $usuario, $idTipo) {
            DB::table('asignacion_cita')
                ->whereDate('fecha_cita', $fecha->toDateString())
                ->where('id_optometra', $data['id_optometra'])
                ->lockForUpdate()
                ->get();

            if (! AgendaCitas::turnoDisponible($fecha, $data['hora'], (int) $data['id_optometra'], $idTipo)) {
                return false;
            }

            // La misma persona no puede tener dos citas a la misma hora
            $cancelado = AgendaCitas::estadoCancelado();
            $yaTiene = DB::table('asignacion_cita')
                ->where('id_usuario', $usuario->documento)
                ->whereDate('fecha_cita', $fecha->toDateString())
                ->where('hora_cita', $data['hora'].':00')
                ->when($cancelado, fn ($q) => $q->where('id_estado', '!=', $cancelado))
                ->exists();
            if ($yaTiene) {
                return false;
            }

            DB::table('asignacion_cita')->insert([
                'fecha_cita' => $fecha->toDateString(),
                'hora_cita' => $data['hora'].':00',
                'observaciones' => $data['comentario'] ?? null,
                'id_usuario' => $usuario->documento,
                'id_optometra' => $data['id_optometra'],
                'id_tipo_cita' => $idTipo,
                'id_estado' => AgendaCitas::estadoPendiente(),
                'id_motivo' => $data['id_motivo'],
            ] + (Schema::hasColumn('asignacion_cita', 'nit_empresa')
                ? ['nit_empresa' => $usuario->nit_empresa ?? session('optica_nit', config('visioptica.empresa_principal'))]
                : []));

            return true;
        });

        if (! $reservada) {
            return back()->withInput()->withErrors([
                'hora' => 'Ese horario acaba de ser tomado por otra persona, o ya tienes una cita a esa hora. Escoge otro.',
            ]);
        }

        return redirect()->route('citas.create')->with('cita_reservada', $resumen);
    }
}
