<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\PaginaEmpresa;
use App\Support\AgendaCitas;
use App\Support\Licencias;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiteController extends Controller
{
    /**
     * Página principal ("/"): muestra la óptica configurada como principal.
     */
    public function inicio()
    {
        $empresa = Empresa::find(config('visioptica.empresa_principal'));
        abort_unless($empresa, 404);

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
    public function planes()
    {
        // Los precios salen de la tabla `plan`, que edita el superadmin.
        $planes = \App\Models\Plan::where('activo', 1)->orderBy('meses')->get();

        return view('planes', compact('planes'));
    }

    /**
     * Procesa el formulario de contacto de la vista "Conócenos".
     *
     * Por ahora el mensaje solo se confirma en pantalla (no se persiste
     * en base de datos porque no existe una tabla para ello). Si más
     * adelante quieres guardarlo o enviarlo por correo, aquí es donde
     * se agregaría esa lógica (Mail::to(...)->send(...) o un insert).
     */
    public function enviarContacto(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        return back()->with('contacto_enviado', true);
    }

    /**
     * Vista pública: agendar una cita.
     *
     * La persona escoge el día y uno de los turnos libres (según los
     * horarios de los optómetras). Si inició sesión, la cita queda
     * reservada de una vez; si no, se guarda como solicitud en
     * `usuarios_no_registrados` para que el administrador la confirme.
     */
    public function agendarCita()
    {
        $usuario = Auth::user();

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

        return view('agendar-cita', [
            'motivos' => DB::table('motivos_cita')->orderBy('nombre_motivo')->get(),
            'tiposCita' => DB::table('tipo_cita')->orderBy('nombre_tipo')->get(),
            'tiposDocumento' => DB::table('tipo_documento')->orderBy('id_tipo_docu')->get(),
            'diasAtencion' => AgendaCitas::diasConAtencion(),
            'usuario' => $usuario,
            'proximasCitas' => $proximasCitas,
            'fechaMin' => now()->toDateString(),
            'fechaMax' => now()->addDays(AgendaCitas::DIAS_MAXIMOS)->toDateString(),
        ]);
    }

    /**
     * Turnos libres de un día (lo consulta el formulario al escoger la fecha).
     */
    public function horariosDisponibles(Request $request): JsonResponse
    {
        $request->validate(['fecha' => ['required', 'date_format:Y-m-d']]);

        $fecha = Carbon::createFromFormat('Y-m-d', $request->fecha)->startOfDay();

        return response()->json([
            'dia' => AgendaCitas::nombreDia($fecha),
            'turnos' => AgendaCitas::turnosLibres($fecha),
        ]);
    }

    /**
     * Guarda la cita (usuario con sesión) o la solicitud (visitante).
     */
    public function guardarCita(Request $request)
    {
        $usuario = Auth::user();

        $reglas = [
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'id_optometra' => ['required', 'integer', 'exists:optometra,doc_optometra'],
            'id_tipo_cita' => ['required', 'integer', 'exists:tipo_cita,id_tipo_cita'],
            'id_motivo' => ['required', 'integer', 'exists:motivos_cita,id_motivo'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ];

        if (! $usuario) {
            $reglas += [
                'nombre' => ['required', 'string', 'max:100'],
                'id_tipo_docu' => ['required', 'integer', 'exists:tipo_documento,id_tipo_docu'],
                'documento' => ['required', 'digits_between:5,15'],
                'telefono' => ['required', 'string', 'max:20'],
                'correo' => ['required', 'email', 'max:100'],
            ];
        }

        $data = $request->validate($reglas, [
            'hora.required' => 'Escoge uno de los horarios disponibles.',
            'id_optometra.required' => 'Escoge uno de los horarios disponibles.',
            'fecha.after_or_equal' => 'La fecha de la cita no puede ser en el pasado.',
        ], [
            'fecha' => 'fecha de la cita',
            'hora' => 'hora de la cita',
            'id_optometra' => 'optómetra',
            'id_tipo_cita' => 'tipo de cita',
            'id_motivo' => 'motivo de la cita',
            'id_tipo_docu' => 'tipo de documento',
        ]);

        $fecha = Carbon::createFromFormat('Y-m-d', $data['fecha'])->startOfDay();

        // Un visitante con un documento ya registrado debe iniciar sesión
        if (! $usuario && DB::table('usuario')->where('documento', $data['documento'])->exists()) {
            return back()->withInput()->withErrors([
                'documento' => 'Ya tienes una cuenta con este documento. Inicia sesión para reservar tu cita.',
            ]);
        }

        $motivo = DB::table('motivos_cita')->where('id_motivo', $data['id_motivo'])->value('nombre_motivo');
        $tipo = DB::table('tipo_cita')->where('id_tipo_cita', $data['id_tipo_cita'])->value('nombre_tipo');
        $optometra = DB::table('optometra')->where('doc_optometra', $data['id_optometra'])
            ->selectRaw("CONCAT(nombre, ' ', apellido) as n")->value('n');

        $resumen = [
            'fecha' => ucfirst($fecha->locale('es')->translatedFormat('l j \d\e F \d\e Y')),
            'hora' => $data['hora'],
            'optometra' => $optometra,
            'tipo' => $tipo,
        ];

        if ($usuario) {
            // Se vuelve a revisar el turno dentro de una transacción para
            // que dos personas no reserven la misma hora al mismo tiempo.
            $reservada = DB::transaction(function () use ($data, $fecha, $usuario) {
                DB::table('asignacion_cita')
                    ->whereDate('fecha_cita', $fecha->toDateString())
                    ->where('id_optometra', $data['id_optometra'])
                    ->lockForUpdate()
                    ->get();

                if (! AgendaCitas::turnoDisponible($fecha, $data['hora'], (int) $data['id_optometra'])) {
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
                    'id_tipo_cita' => $data['id_tipo_cita'],
                    'id_estado' => AgendaCitas::estadoPendiente(),
                    'id_motivo' => $data['id_motivo'],
                ] + (Schema::hasColumn('asignacion_cita', 'nit_empresa')
                    ? ['nit_empresa' => $usuario->nit_empresa ?? session('optica_nit', config('visioptica.empresa_principal'))]
                    : []));

                return true;
            });

            if (! $reservada) {
                return back()->withInput()->withErrors([
                    'hora' => 'Ese horario acaba de ser tomado por otra persona. Por favor escoge otro.',
                ]);
            }

            return redirect()->route('citas.create')->with('cita_reservada', $resumen);
        }

        // Visitante: queda como solicitud y el turno escogido queda apartado

        $detalle = "{$motivo} — {$tipo}. Prefiere: {$fecha->toDateString()} a las {$data['hora']} con {$optometra}.";
        if (! empty($data['comentario'])) {
            $detalle .= ' Comentario: '.$data['comentario'];
        }

        $registro = [
            'documento' => $data['documento'],
            'correo' => $data['correo'],
            'nombre' => $data['nombre'],
            'telefono' => $data['telefono'],
            'motivo_cita' => $detalle,
            'fecha_registro' => now(),
            // Óptica cuya página estaba viendo el visitante
            'nit_empresa' => session('optica_nit', config('visioptica.empresa_principal')),
        ];
        if (Schema::hasColumn('usuarios_no_registrados', 'id_tipo_docu')) {
            $registro['id_tipo_docu'] = $data['id_tipo_docu'];
        }
        if (AgendaCitas::solicitudesGuardanTurno()) {
            // Se guarda el turno escogido para que quede apartado y nadie más lo tome
            $registro['fecha_cita'] = $fecha->toDateString();
            $registro['hora_cita'] = $data['hora'].':00';
            $registro['id_optometra'] = $data['id_optometra'];
            $registro['id_estado'] = AgendaCitas::estadoPendiente();
        }

        $guardada = DB::transaction(function () use ($registro, $fecha, $data) {
            DB::table('usuarios_no_registrados')->lockForUpdate()->whereDate('fecha_registro', now()->toDateString())->get(['id_usuario_nr']);

            if (! AgendaCitas::turnoDisponible($fecha, $data['hora'], (int) $data['id_optometra'])) {
                return false;
            }

            DB::table('usuarios_no_registrados')->insert($registro);

            return true;
        });

        if (! $guardada) {
            return back()->withInput()->withErrors([
                'hora' => 'Ese horario ya no está disponible. Por favor escoge otro.',
            ]);
        }

        return redirect()->route('citas.create')->with('cita_enviada', $resumen);
    }
}
