<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmpleadoCredencialesMail;
use App\Models\Actividad;
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

    protected function empleados()
    {
        return Usuario::where('nit_empresa', Auth::user()->nit_empresa)
            ->whereIn('id_rol', $this->rolesAsignables()->pluck('id_rol'));
    }

    protected function empleado(int $documento): Usuario
    {
        return $this->empleados()->where('documento', $documento)->firstOrFail();
    }

    public function index(): View
    {
        return view('admin.equipo.index', [
            'empleados' => $this->empleados()->with('rol')->orderBy('nombres')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.equipo.form', [
            'empleado' => null,
            'roles' => $this->rolesAsignables(),
            'tiposDoc' => DB::table('tipo_documento')->orderBy('id_tipo_docu')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        abort_unless($empresa, 403);

        $data = $request->validate([
            'id_tipo_docu' => ['required', 'integer', 'exists:tipo_documento,id_tipo_docu'],
            'documento' => ['required', 'integer', 'min:1', 'unique:usuario,documento'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', 'unique:usuario,telefono'],
            'email' => ['required', 'email', 'max:100', 'unique:usuario,email'],
            'id_rol' => ['required', Rule::in($this->rolesAsignables()->pluck('id_rol')->all())],
        ], [
            'documento.unique' => 'Ya existe un usuario con ese documento.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'telefono.unique' => 'Ya existe un usuario con ese celular.',
            'id_rol.in' => 'Elige un rol de la lista.',
        ], ['id_tipo_docu' => 'tipo de documento', 'id_rol' => 'rol', 'telefono' => 'celular', 'email' => 'correo']);

        $password = Str::password(10, true, true, false);

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

        Actividad::registrar($empresa->nit, 'crear', "Creó al empleado {$empleado->nombres} ({$empleado->rol->nombre_rol})", 'usuario', $empleado->documento);

        return redirect()->route('admin.equipo.index')->with('success', $this->resultadoCorreo($empleado, $password, false));
    }

    public function edit(int $documento): View
    {
        return view('admin.equipo.form', [
            'empleado' => $this->empleado($documento),
            'roles' => $this->rolesAsignables(),
            'tiposDoc' => collect(),
        ]);
    }

    public function update(Request $request, int $documento): RedirectResponse
    {
        $empleado = $this->empleado($documento);

        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', Rule::unique('usuario', 'telefono')->ignore($empleado->documento, 'documento')],
            'email' => ['required', 'email', 'max:100', Rule::unique('usuario', 'email')->ignore($empleado->documento, 'documento')],
            'id_rol' => ['required', Rule::in($this->rolesAsignables()->pluck('id_rol')->all())],
        ], [], ['id_rol' => 'rol', 'telefono' => 'celular', 'email' => 'correo']);

        $empleado->update($data);

        Actividad::registrar($empleado->nit_empresa, 'editar', "Actualizó al empleado {$empleado->nombres}", 'usuario', $empleado->documento);

        return redirect()->route('admin.equipo.index')->with('success', 'Empleado actualizado.');
    }

    /** Activa o desactiva la cuenta (uno inactivo no puede iniciar sesión). */
    public function estado(int $documento): RedirectResponse
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
    public function reenviar(int $documento): RedirectResponse
    {
        $empleado = $this->empleado($documento);
        $password = Str::password(10, true, true, false);

        $empleado->update(['password' => Hash::make($password), 'id_estado' => 3]);

        Actividad::registrar($empleado->nit_empresa, 'editar', "Restableció la contraseña de {$empleado->nombres}", 'usuario', $empleado->documento);

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
