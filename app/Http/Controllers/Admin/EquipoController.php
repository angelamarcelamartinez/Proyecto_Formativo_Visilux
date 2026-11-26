<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmpleadoCredencialesMail;
use App\Models\Actividad;
use App\Models\Optometra;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\BloqueoUsuarios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Equipo de la óptica: el administrador crea a sus empleados (vendedor, recepcionista, auditor...),
 * y a cada uno le llega al correo su usuario y una contraseña temporal que debe cambiar al entrar.
 * También los activa, desactiva y les reenvía credenciales. Solo ve y toca los de SU óptica.
 *
 * El OPTÓMETRA es distinto: no se guarda en `usuario`. Toda su información y su cuenta (correo y
 * contraseña) quedan en la tabla `optometra` (modelo App\Models\Optometra). Por eso cada acción
 * mira primero si el empleado es un Optometra o un Usuario.
 */
class EquipoController extends Controller
{
    /** Roles que el administrador puede asignar (no el de administrador, superadmin ni cliente). */
    protected function rolesAsignables()
    {
        return Rol::whereNotIn('id_rol', [
            (int) config('visioptica.rol_admin'),
            (int) config('visioptica.rol_superadmin'),
            2, // Cliente: se registra solo desde el sitio público
        ])->orderBy('nombre_rol')->get();
    }

    protected function rolOptometra(): int
    {
        return Optometra::rolId();
    }

    /** Empleados guardados en `usuario` (todos menos el optómetra). */
    protected function empleados()
    {
        return Usuario::where('nit_empresa', Auth::user()->nit_empresa)
            ->whereIn('id_rol', $this->rolesAsignables()->pluck('id_rol'))
            ->where('id_rol', '!=', $this->rolOptometra());
    }

    /** Optómetras de la óptica que ya tienen cuenta (tabla `optometra`). */
    protected function optometras()
    {
        return Optometra::where('nit_empresa', Auth::user()->nit_empresa)->whereNotNull('contrasena');
    }

    /** Busca al empleado en `usuario` y, si no está, en `optometra`. */
    protected function empleado(string $documento): Usuario
    {
        return $this->empleados()->where('documento', $documento)->first()
            ?? $this->optometras()->where('doc_optometra', $documento)->firstOrFail();
    }

    public function index(): View
    {
        $empleados = $this->empleados()->with('rol')->get()
            ->concat($this->optometras()->get())
            ->sortBy(fn ($e) => mb_strtolower($e->nombres))
            ->values();

        return view('admin.equipo.index', ['empleados' => $empleados]);
    }

    /**
     * Documento, correo y celular no se pueden repetir entre `usuario` y `optometra`.
     * Devuelve los errores (vacío si todo está bien). $ignorar = documento del optómetra que se está editando.
     */
    protected function repetidos(array $data, ?string $ignorarUsuario = null, ?string $ignorarOptometra = null): array
    {
        $errores = [];
        $campos = [
            'documento' => ['documento', 'doc_optometra', 'Ya existe una persona registrada con ese documento.'],
            'email' => ['email', 'correo', 'Ya existe una persona registrada con ese correo.'],
            'telefono' => ['telefono', 'telefono', 'Ya existe una persona registrada con ese celular.'],
        ];

        foreach ($campos as $campo => [$enUsuario, $enOptometra, $mensaje]) {
            if (! array_key_exists($campo, $data)) {
                continue;
            }
            $valor = (string) $data[$campo];
            $usuario = DB::table('usuario')->where($enUsuario, $valor)
                ->when($ignorarUsuario, fn ($q) => $q->where('documento', '!=', $ignorarUsuario))->exists();
            $optometra = DB::table('optometra')->where($enOptometra, $valor)
                ->when($ignorarOptometra, fn ($q) => $q->where('doc_optometra', '!=', $ignorarOptometra))->exists();
            if ($usuario || $optometra) {
                $errores[$campo] = $mensaje;
            }
        }

        return $errores;
    }

    public function create(): View
    {
        return view('admin.equipo.form', [
            'empleado' => null,
            'roles' => $this->rolesAsignables(),
            'tiposDoc' => DB::table('tipo_documento')->orderBy('id_tipo_docu')->get(),
            'especialidades' => DB::table('especialidad')->orderBy('nombre_especialidad')->get(),
            'optometra' => null,
            'optometrasSinUsuario' => $this->optometrasSinUsuario(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        abort_unless($empresa, 403);

        $request->merge(['documento' => \App\Support\TipoDocumento::normalizar($request->input('documento'))]);

        $data = $request->validate([
            'id_tipo_docu' => ['required', 'integer', 'exists:tipo_documento,id_tipo_docu'],
            'documento' => ['required', 'string', 'max:20', new \App\Rules\DocumentoValido($request->input('id_tipo_docu'))],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100'],
            'id_rol' => ['required', Rule::in($this->rolesAsignables()->pluck('id_rol')->all())],
        ], [
            'documento.unique' => 'Ya existe un usuario con ese documento.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'telefono.unique' => 'Ya existe un usuario con ese celular.',
            'id_rol.in' => 'Elige un rol de la lista.',
        ], ['id_tipo_docu' => 'tipo de documento', 'id_rol' => 'rol', 'telefono' => 'celular', 'email' => 'correo']);

        $profesional = $this->datosOptometra($request, (int) $data['id_rol']);

        // Optómetra que ya estaba en la tabla `optometra` (con citas y horarios) pero sin cuenta.
        $existente = $profesional ? $this->optometraExistente($request, $profesional, (string) $data['documento']) : null;
        if ($existente instanceof RedirectResponse) {
            return $existente;
        }

        // Documento, correo y celular: únicos entre `usuario` y `optometra` (sin contar al optómetra elegido).
        if ($errores = $this->repetidos($data, null, $existente?->doc_optometra)) {
            return back()->withInput()->withErrors($errores);
        }

        $password = Str::password(10, true, true, false);

        try {
            DB::beginTransaction();

            if ($profesional) {
                // OPTÓMETRA: todo se guarda en la tabla `optometra`, nada en `usuario`.
                if ($existente && (string) $existente->doc_optometra !== (string) $data['documento']) {
                    // Su registro tenía otro número (por ejemplo la tarjeta profesional): pasa a la cédula con sus citas y horarios.
                    $this->cambiarDocumentoOptometra((string) $existente->doc_optometra, (string) $data['documento']);
                }

                $empleado = Optometra::find($data['documento']) ?? new Optometra(['doc_optometra' => $data['documento'], 'fecha_creacion' => now()]);
                $empleado->fill([
                    'nombre' => $data['nombres'],
                    'apellido' => $data['apellido'],
                    'telefono' => $data['telefono'],
                    'correo' => $data['email'],
                    'contrasena' => Hash::make($password),
                    'id_tipo_docu' => $data['id_tipo_docu'],
                    'id_estado' => 3, // Pendiente: debe cambiar la contraseña temporal en su primer ingreso
                    'nit_empresa' => $empresa->nit,
                ])->save();
            } else {
                $empleado = Usuario::create([
                    'documento' => $data['documento'],
                    'nombres' => $data['nombres'],
                    'apellido' => $data['apellido'],
                    'telefono' => $data['telefono'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'fecha_creacion' => now(),
                    'id_rol' => $data['id_rol'],
                    'id_tipo_docu' => $data['id_tipo_docu'],
                    'id_estado' => 3, // Pendiente: debe cambiar la contraseña temporal en su primer ingreso
                    'nit_empresa' => $empresa->nit,
                ]);
            }

            // Datos profesionales del optómetra (tarjeta y especialidad).
            $this->guardarOptometra($empleado, $profesional);

            DB::commit();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['general' => \App\Http\Controllers\Optometra\PanelController::mensajeBd($e)]);
        }

        Actividad::registrar($empresa->nit, 'crear', "Creó al empleado {$empleado->nombres} ({$empleado->rol->nombre_rol})", $empleado->getTable(), $empleado->documento);

        return redirect()->route('admin.equipo.index')->with('success', $this->resultadoCorreo($empleado, $password, false));
    }

    public function edit(string $documento): View
    {
        $empleado = $this->empleado($documento);

        return view('admin.equipo.form', [
            'empleado' => $empleado,
            // Un optómetra sigue siendo optómetra (su cuenta está en otra tabla) y los demás no pasan a serlo.
            'roles' => $this->rolesAsignables()->filter(fn ($r) => ((int) $r->id_rol === $this->rolOptometra()) === ($empleado instanceof Optometra))->values(),
            'tiposDoc' => collect(),
            'especialidades' => DB::table('especialidad')->orderBy('nombre_especialidad')->get(),
            'optometra' => DB::table('optometra')->where('doc_optometra', $documento)->first(),
        ]);
    }

    public function update(Request $request, string $documento): RedirectResponse
    {
        $empleado = $this->empleado($documento);

        $esOptometra = $empleado instanceof Optometra;

        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100'],
            'id_rol' => ['required', Rule::in($this->rolesAsignables()->pluck('id_rol')->all())],
        ], [], ['id_rol' => 'rol', 'telefono' => 'celular', 'email' => 'correo']);

        // El optómetra no puede pasar a otro rol ni otro empleado volverse optómetra: son tablas distintas.
        if (((int) $data['id_rol'] === $this->rolOptometra()) !== $esOptometra) {
            return back()->withInput()->withErrors(['id_rol' => $esOptometra
                ? 'Un optómetra no puede cambiar de rol. Desactívalo y crea la persona de nuevo con el otro rol.'
                : 'Para que sea optómetra, desactiva este empleado y créalo de nuevo con el rol Optómetra.']);
        }

        $errores = $this->repetidos(['email' => $data['email'], 'telefono' => $data['telefono']],
            $esOptometra ? null : $empleado->documento, $esOptometra ? $empleado->documento : null);
        if ($errores) {
            return back()->withInput()->withErrors($errores);
        }

        $profesional = $this->datosOptometra($request, (int) $data['id_rol']);

        try {
            DB::transaction(function () use ($empleado, $data, $profesional, $esOptometra) {
                if ($esOptometra) {
                    unset($data['id_rol']);
                }
                $empleado->update($data);
                $this->guardarOptometra($empleado, $profesional);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->withErrors(['general' => \App\Http\Controllers\Optometra\PanelController::mensajeBd($e)]);
        }

        Actividad::registrar($empleado->nit_empresa, 'editar', "Actualizó al empleado {$empleado->nombres}", $empleado->getTable(), $empleado->documento);

        return redirect()->route('admin.equipo.index')->with('success', 'Empleado actualizado.');
    }

    /** Datos profesionales del optómetra (solo si el rol elegido es Optómetra). */
    protected function datosOptometra(Request $request, int $idRol): ?array
    {
        if ($idRol !== (int) config('visioptica.rol_optometra', 7)) {
            return null;
        }

        return $request->validate([
            'tarjeta_profesional' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'id_especialidad' => ['nullable', 'integer', 'exists:especialidad,id_especialidad'],
            'optometra_existente' => ['nullable', 'string', 'max:20'],
        ], [
            'tarjeta_profesional.required' => 'El optómetra necesita su número de tarjeta profesional.',
        ], ['tarjeta_profesional' => 'tarjeta profesional', 'id_especialidad' => 'especialidad']);
    }

    /** Optómetras de la óptica que están en la tabla `optometra` pero todavía no tienen cuenta (sin contraseña). */
    protected function optometrasSinUsuario()
    {
        $nit = Auth::user()->empresa?->nit;

        return DB::table('optometra')
            ->where(fn ($q) => $q->where('nit_empresa', $nit)->orWhereNull('nit_empresa'))
            ->whereNull('contrasena')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Revisa el optómetra elegido en "¿Ya está en la lista?" y que la tarjeta profesional no sea de otro.
     * Devuelve la fila elegida, null si es nuevo, o una redirección con el error.
     */
    protected function optometraExistente(Request $request, array $profesional, string $cedula)
    {
        $sinUsuario = $this->optometrasSinUsuario()->keyBy('doc_optometra');
        $elegido = null;

        if (! empty($profesional['optometra_existente'])) {
            $elegido = $sinUsuario->get((string) $profesional['optometra_existente']);
            if (! $elegido) {
                return back()->withInput()->withErrors(['optometra_existente' => 'Ese optómetra ya tiene cuenta o no es de tu óptica.']);
            }
            // La cédula nueva no puede ser el registro de OTRO optómetra.
            if ($cedula !== (string) $elegido->doc_optometra && DB::table('optometra')->where('doc_optometra', $cedula)->exists()) {
                return back()->withInput()->withErrors(['documento' => 'Ya hay otro optómetra registrado con esa cédula.']);
            }
        } else {
            // Optómetra nuevo: si su cédula ya está en la lista, debe elegirse arriba para no duplicarlo.
            $mismo = $sinUsuario->get($cedula);
            if ($mismo) {
                $elegido = $mismo;
            }
        }

        // La tarjeta profesional es única dentro de la óptica.
        $duenoTarjeta = DB::table('optometra')
            ->where('tarjeta_profesional', $profesional['tarjeta_profesional'])
            ->where(fn ($q) => $q->where('nit_empresa', Auth::user()->empresa?->nit)->orWhereNull('nit_empresa'))
            ->when($elegido, fn ($q) => $q->where('doc_optometra', '!=', $elegido->doc_optometra))
            ->first();

        if ($duenoTarjeta) {
            return back()->withInput()->withErrors(['tarjeta_profesional' =>
                "La tarjeta profesional {$profesional['tarjeta_profesional']} ya es de {$duenoTarjeta->nombre} {$duenoTarjeta->apellido}. "
                . 'Si es la misma persona, elígela en "¿Ya está en la lista de optómetras?".']);
        }

        return $elegido;
    }

    /**
     * Cambia el número de un optómetra (doc_optometra) y mueve con él sus citas, horarios y simulaciones.
     * asignacion_cita tiene la llave foránea en RESTRICT, por eso se desactiva un momento la revisión.
     */
    protected function cambiarDocumentoOptometra(string $anterior, string $nuevo): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            DB::table('optometra')->where('doc_optometra', $anterior)->update(['doc_optometra' => $nuevo]);
            $columnas = ['asignacion_cita' => 'id_optometra', 'horario' => 'id_optometra', 'simulacion_visual' => 'id_optometra',
                'formula_medica' => 'doc_optometra', 'actividad' => 'doc_optometra'];
            foreach ($columnas as $tabla => $columna) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($tabla, $columna)) {
                    DB::table($tabla)->where($columna, $anterior)->update([$columna => $nuevo]);
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    protected function guardarOptometra(Usuario $empleado, ?array $profesional): void
    {
        if ($profesional === null) {
            return;
        }

        $cambios = array_filter([
            'tarjeta_profesional' => $profesional['tarjeta_profesional'] ?? null,
            'id_especialidad' => $profesional['id_especialidad'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($cambios) {
            DB::table('optometra')->where('doc_optometra', $empleado->documento)->update($cambios);
        }
    }

    /** Activa o desactiva la cuenta (uno inactivo no puede iniciar sesión). */
    public function estado(string $documento): RedirectResponse
    {
        $empleado = $this->empleado($documento);
        $nombre = trim($empleado->nombres . ' ' . $empleado->apellido);

        // BloqueoUsuarios recuerda el estado anterior (p. ej. «Pendiente» si aún no cambió su contraseña
        // temporal) y, al activar, le avisa por correo al empleado.
        if (BloqueoUsuarios::estaInactivo($empleado) || (int) $empleado->id_estado === 4) {
            $correo = BloqueoUsuarios::activar($empleado, 'Empleado activado por el administrador');

            return back()->with('success', "{$nombre} fue activado y ya puede iniciar sesión."
                . ($correo ? " Le enviamos un correo a {$empleado->email}." : ' (No se pudo enviar el correo de aviso.)'));
        }

        BloqueoUsuarios::inactivar($empleado, 'Empleado desactivado por el administrador');

        return back()->with('success', "{$nombre} fue desactivado y ya no puede entrar.");
    }

    /** Genera una contraseña temporal nueva y la envía al correo del empleado. */
    public function reenviar(string $documento): RedirectResponse
    {
        $empleado = $this->empleado($documento);
        $password = Str::password(10, true, true, false);

        // Una cuenta inactiva (2) no se reactiva por restablecer la contraseña: primero hay que activarla.
        if ((int) $empleado->id_estado === 2) {
            return back()->withErrors(['general' => 'Esa cuenta está inactiva. Actívala antes de restablecer su contraseña.']);
        }

        $empleado->update(['password' => Hash::make($password), 'id_estado' => 3]);

        Actividad::registrar($empleado->nit_empresa, 'editar', "Restableció la contraseña de {$empleado->nombres}", $empleado->getTable(), $empleado->documento);

        return back()->with('success', $this->resultadoCorreo($empleado, $password, true));
    }

    /** Envía las credenciales; si el correo falla, le muestra la contraseña al administrador para que se la entregue. */
    protected function resultadoCorreo(Usuario $empleado, string $password, bool $restablecida): string
    {
        $empresa = Auth::user()->empresa;
        $rol = $empleado->rol->nombre_rol ?? 'empleado';

        try {
            Mail::to($empleado->email)->send(new EmpleadoCredencialesMail($empresa, $empleado, $rol, $password, $restablecida));

            return ($restablecida ? "Contraseña temporal nueva enviada" : "Empleado creado. Le enviamos sus credenciales")
                . " a {$empleado->email}. Al entrar por primera vez deberá cambiarla.";
        } catch (\Throwable $e) {
            report($e);

            return "{$empleado->nombres} quedó guardado, pero no se pudo enviar el correo. Entrégale estos datos: usuario {$empleado->email}, contraseña temporal {$password}";
        }
    }
}
