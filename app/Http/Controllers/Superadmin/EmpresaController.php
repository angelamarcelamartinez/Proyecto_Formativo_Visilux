<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\PaginaEmpresa;
use App\Models\Plan;
use App\Models\Usuario;
use App\Support\CamaraComercio;
use App\Support\Licencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    /**
     * Lista de ópticas con su plan, días restantes y estado.
     */
    public function index(Request $request): View
    {
        Licencias::marcarVencidas();

        $estado = $request->get('estado', 'todas');
        $q = trim((string) $request->get('q', ''));

        $query = DB::table('v_estado_licencias');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('empresa', 'like', "%{$q}%")
                    ->orWhere('nit', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // El filtro por estado se hace en PHP: estado_licencia es una columna calculada
        // de la vista y compararla en SQL puede chocar con la collation de la conexión.
        $opticas = $query->orderBy('empresa')->get();
        $conteos = $opticas->countBy('estado_licencia');

        if ($estado !== 'todas') {
            $opticas = $opticas->where('estado_licencia', $estado)->values();
        }

        // Administradores de cada óptica (nombre, usuario para entrar y si ya cambió la contraseña temporal)
        $admins = Usuario::whereIn('nit_empresa', $opticas->pluck('nit'))
            ->where('id_rol', config('visioptica.rol_admin'))
            ->orderBy('nombres')
            ->get(['documento', 'nombres', 'apellido', 'email', 'id_estado', 'nit_empresa', 'ultimo_acceso'])
            ->groupBy('nit_empresa');

        return view('superadmin.empresas.index', compact('opticas', 'estado', 'q', 'conteos', 'admins'));
    }

    /**
     * Ficha de una óptica: datos, licencias, usuarios y movimiento.
     */
    public function show(string $nit): View
    {
        Licencias::marcarVencidas();

        $empresa = Empresa::findOrFail($nit);
        $resumen = DB::table('v_estado_licencias')->where('nit', $nit)->first();

        $licencias = $empresa->licencias()->with('plan')
            ->orderByDesc('fecha_solicitud')
            ->get();

        $usuariosPorRol = DB::table('usuario')
            ->join('rol', 'rol.id_rol', '=', 'usuario.id_rol')
            ->where('usuario.nit_empresa', $nit)
            ->select('rol.nombre_rol', DB::raw('COUNT(*) as total'))
            ->groupBy('rol.nombre_rol')
            ->orderByDesc('total')
            ->get();

        $admins = Usuario::where('nit_empresa', $nit)
            ->where('id_rol', config('visioptica.rol_admin'))
            ->get(['documento', 'nombres', 'apellido', 'email', 'ultimo_acceso', 'id_estado']);

        $inicioMes = now()->startOfMonth();

        // Si la base de datos todavía no tiene la columna nit_empresa en alguna tabla,
        // esa cifra se muestra en 0 en vez de romper la ficha de la óptica.
        $consulta = fn (string $tabla) => Schema::hasColumn($tabla, 'nit_empresa')
            ? DB::table($tabla)->where('nit_empresa', $nit)
            : DB::table($tabla)->whereRaw('1 = 0');

        $movimiento = [
            'citas_total' => $consulta('asignacion_cita')->count(),
            'citas_mes' => $consulta('asignacion_cita')->where('fecha_cita', '>=', $inicioMes)->count(),
            'ventas_total' => (float) $consulta('venta')->sum('total')
                + (float) $consulta('venta_medicamento')->sum('total'),
            'ventas_mes' => (float) $consulta('venta')->where('fecha_venta', '>=', $inicioMes)->sum('total')
                + (float) $consulta('venta_medicamento')->where('fecha', '>=', $inicioMes)->sum('total'),
            'historias' => $consulta('historia_clinica')->count(),
            'productos' => $consulta('producto')->count(),
        ];

        $actividad = Actividad::with('usuario')
            ->where('nit_empresa', $nit)
            ->orderByDesc('fecha')
            ->limit(15)
            ->get();

        $planes = Plan::where('activo', 1)->orderBy('meses')->get();

        return view('superadmin.empresas.show', compact(
            'empresa', 'resumen', 'licencias', 'usuariosPorRol', 'admins', 'movimiento', 'actividad', 'planes'
        ));
    }

    public function edit(string $nit): View
    {
        return view('superadmin.empresas.form', [
            'empresa' => Empresa::findOrFail($nit),
        ]);
    }

    public function update(Request $request, string $nit): RedirectResponse
    {
        $empresa = Empresa::findOrFail($nit);
        $pendiente = $empresa->estaPendiente();

        // El NIT se guarda como número: sin puntos ni guion (800000000-9 → 8000000009).
        $request->merge(['nit' => \App\Support\Nit::normalizar($request->input('nit'))]);

        $reglas = $this->reglasEmpresa($empresa);

        // Todos los campos se pueden editar, incluido el NIT (se cambia en cascada en todas las tablas).
        $reglas['nit'] = array_merge(\App\Support\Nit::reglas(), [Rule::unique('empresa', 'nit')->ignore($empresa->nit, 'nit')]);

        if ($pendiente) {
            // Óptica nueva que aún no se aprueba: el PDF solo hace falta si no tiene uno; la verificación
            // con la Cámara de Comercio se confirma al aprobar (ventana «Verificar NIT y aprobar»).
            $reglas['camara_comercio'] = CamaraComercio::reglas(! $empresa->tieneCamara());
        } else {
            // Modificar una óptica ya activa obliga a verificar su NIT otra vez: PDF nuevo + confirmación.
            $reglas['camara_comercio'] = CamaraComercio::reglas();
            $reglas['confirmo'] = ['accepted'];
        }

        $data = $request->validate($reglas, $this->mensajesVerificacion(), $this->nombresCampos());

        $nuevoNit = (string) \App\Support\Nit::normalizar($data['nit']);
        $cambioNit = $nuevoNit !== (string) $empresa->nit;

        $anterior = $empresa->camara_de_comercio;
        $rutaCamara = $request->hasFile('camara_comercio')
            ? CamaraComercio::guardar($request->file('camara_comercio'), $nuevoNit)
            : null;

        try {
            DB::transaction(function () use ($empresa, $data, $nuevoNit, $cambioNit, $rutaCamara) {
                if ($cambioNit) {
                    $this->cambiarNit($empresa->nit, $nuevoNit);
                    $empresa = Empresa::findOrFail($nuevoNit);
                }

                $empresa->update(array_filter([
                    'camara_de_comercio' => $rutaCamara,
                ]) + [
                    'nombre' => $data['nombre'],
                    'slug' => Empresa::slugDisponible(($data['slug'] ?? null) ?: $data['nombre'], $empresa->nit),
                    'email' => $data['email'],
                    'telefono' => $data['telefono'] ?? null,
                    'direccion' => $data['direccion'] ?? null,
                    'ciudad' => $data['ciudad'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            CamaraComercio::borrar($rutaCamara);
            throw $e;
        }

        if ($rutaCamara) {
            CamaraComercio::borrar($anterior);
        }

        Actividad::registrar($nuevoNit, 'editar',
            $pendiente
                ? 'Datos de la óptica corregidos antes de aprobar' . ($cambioNit ? " (NIT {$nit} → {$nuevoNit})" : '')
                : 'Datos de la óptica actualizados y NIT verificado con la Cámara de Comercio' . ($cambioNit ? " (NIT {$nit} → {$nuevoNit})" : ''),
            'empresa', $nuevoNit);

        if ($pendiente) {
            return redirect()->route('superadmin.dashboard')
                ->with('success', 'Datos de la óptica guardados' . ($cambioNit ? " (NIT corregido a {$nuevoNit})" : '') . '. Ya puedes verificar el NIT y aprobar.');
        }

        return redirect()->route('superadmin.empresas.show', $nuevoNit)
            ->with('success', 'Datos de la óptica actualizados y NIT verificado.');
    }

    /**
     * Cambia el NIT de una óptica en todas las tablas que lo usan. La licencia, la página y la actividad
     * lo siguen solas (ON UPDATE CASCADE); el resto (usuarios, etc.) se actualiza aquí.
     */
    protected function cambiarNit(string $viejo, string $nuevo): void
    {
        DB::table('empresa')->where('nit', $viejo)->update(['nit' => $nuevo]);

        $tablas = DB::table('information_schema.columns as c')
            ->join('information_schema.tables as t', fn ($j) => $j->on('t.table_schema', '=', 'c.table_schema')->on('t.table_name', '=', 'c.table_name'))
            ->where('c.table_schema', DB::getDatabaseName())
            ->where('c.column_name', 'nit_empresa')
            ->where('t.table_type', 'BASE TABLE')
            ->pluck('c.table_name');

        foreach ($tablas as $tabla) {
            DB::table($tabla)->where('nit_empresa', $viejo)->update(['nit_empresa' => $nuevo]);
        }
    }

    // ------------------------------------------------- administradores de la óptica

    /** Administradores de una óptica, con el botón para editar a cada uno. */
    public function administradores(string $nit): View
    {
        $empresa = Empresa::findOrFail($nit);

        $admins = Usuario::where('nit_empresa', $nit)
            ->where('id_rol', config('visioptica.rol_admin'))
            ->orderBy('nombres')
            ->get();

        return view('superadmin.empresas.administradores', compact('empresa', 'admins'));
    }

    public function editarAdmin(string $nit, int $documento): View
    {
        return view('superadmin.empresas.administrador-form', [
            'empresa' => Empresa::findOrFail($nit),
            'admin' => $this->adminDe($nit, $documento),
        ]);
    }

    public function actualizarAdmin(Request $request, string $nit, int $documento): RedirectResponse
    {
        $empresa = Empresa::findOrFail($nit);
        $admin = $this->adminDe($nit, $documento);

        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellido' => ['nullable', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', Rule::unique('usuario', 'telefono')->ignore($admin->documento, 'documento')],
            'email' => ['required', 'email', 'max:100', Rule::unique('usuario', 'email')->ignore($admin->documento, 'documento')],
            'password' => ['nullable', 'string', 'min:8'],
            'activo' => ['nullable', 'boolean'],
        ], [], ['nombres' => 'nombres', 'apellido' => 'apellidos', 'telefono' => 'celular', 'email' => 'correo', 'password' => 'contraseña']);

        $cambios = [
            'nombres' => $data['nombres'],
            'apellido' => $data['apellido'] ?? '',
            'telefono' => $data['telefono'],
            'email' => $data['email'],
        ];

        $conClave = ! empty($data['password']);
        if ($conClave) {
            $cambios['password'] = Hash::make($data['password']);
            // Una contraseña puesta por el superadmin se cambia en el próximo ingreso (estado «Pendiente»)
            if ($request->boolean('forzar_cambio') && in_array((int) $admin->id_estado, [1, 3], true)) {
                $cambios['id_estado'] = 3;
            }
        }

        // El superadmin activa o desactiva al administrador (uno inactivo no puede entrar).
        $activar = $request->boolean('activo');
        $estabaInactivo = \App\Support\BloqueoUsuarios::estaInactivo($admin) || (int) $admin->id_estado === 4;
        $cambioEstado = null;
        if ($activar && $estabaInactivo) {
            $cambioEstado = 'activar';
        } elseif (! $activar && ! $estabaInactivo) {
            $cambioEstado = 'inactivar';
        }

        $admin->update($cambios);

        if ($cambioEstado === 'activar') {
            $admin = $admin->fresh();
            \App\Support\BloqueoUsuarios::activar($admin, 'Administrador activado por VisiOptica');
        } elseif ($cambioEstado === 'inactivar') {
            \App\Support\BloqueoUsuarios::inactivar($admin->fresh(), 'Administrador desactivado por VisiOptica');
        }

        Actividad::registrar($empresa->nit, 'editar',
            "Administrador {$admin->nombres} actualizado por el superadmin" . ($conClave ? ' (nueva contraseña)' : '')
            . ($cambioEstado === 'inactivar' ? ' (desactivado)' : ''),
            'usuario', $admin->documento);

        return redirect()->route('superadmin.empresas.admins', $empresa->nit)
            ->with('success', 'Administrador actualizado' . ($conClave ? ' y contraseña cambiada.' : '.'));
    }

    protected function adminDe(string $nit, int $documento): Usuario
    {
        return Usuario::where('nit_empresa', $nit)
            ->where('id_rol', config('visioptica.rol_admin'))
            ->where('documento', $documento)
            ->firstOrFail();
    }

    /**
     * Muestra el PDF de la Cámara de Comercio (archivo privado: solo lo ve el superadmin).
     */
    public function camara(string $nit)
    {
        $empresa = Empresa::findOrFail($nit);
        abort_unless($empresa->tieneCamara(), 404, 'Esta óptica no tiene certificado cargado.');

        return response()->file(CamaraComercio::ruta($empresa->camara_de_comercio), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="camara-comercio-' . $empresa->nit . '.pdf"',
        ]);
    }

    /**
     * Asignar o renovar un plan directamente (pago recibido por fuera del sistema).
     */
    public function asignarLicencia(Request $request, string $nit): RedirectResponse
    {
        $empresa = Empresa::findOrFail($nit);

        if ($empresa->estaPendiente()) {
            return back()->withErrors(['general' => 'Primero aprueba el pago de su registro en el Panel general.']);
        }

        $data = $request->validate([
            'id_plan' => ['required', 'exists:plan,id_plan'],
            'referencia_pago' => ['nullable', 'string', 'max:120'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = Plan::findOrFail($data['id_plan']);

        $licencia = Licencias::asignar(
            $empresa, $plan,
            $data['referencia_pago'] ?? null,
            isset($data['valor']) ? (float) $data['valor'] : null,
            $data['observaciones'] ?? null
        );

        $avisada = Licencias::notificarActivacion($licencia);

        return back()->with('success', "Plan {$plan->nombre} asignado. Cubre del "
            . $licencia->fecha_inicio->format('d/m/Y') . ' al ' . $licencia->fecha_fin->format('d/m/Y') . '.'
            . ($avisada ? ' Le enviamos la confirmación por correo.' : ' No se pudo enviar el correo de confirmación.'));
    }

    /**
     * Activa o desactiva a un administrador de la óptica. Sirve sobre todo cuando
     * el administrador quedó inactivo por intentos fallidos y no hay nadie más
     * en su óptica que pueda activarlo.
     */
    /**
     * Botón «Reenviar credenciales»: genera una contraseña temporal nueva para el administrador
     * y la envía al correo de la óptica y al del administrador.
     */
    public function reenviarCredenciales(string $nit, string $documento): RedirectResponse
    {
        $empresa = Empresa::findOrFail($nit);
        $admin = Usuario::where('nit_empresa', $nit)
            ->where('id_rol', config('visioptica.rol_admin'))
            ->where('documento', $documento)
            ->firstOrFail();

        if ($empresa->estaPendiente()) {
            return back()->withErrors(['general' => "{$empresa->nombre} todavía está pendiente: las credenciales se envían al aprobar el pago."]);
        }

        $licencia = $empresa->licencias()->orderByDesc('id_licencia')->first();
        if (! $licencia) {
            return back()->withErrors(['general' => "{$empresa->nombre} no tiene licencia: asígnale una antes de enviar credenciales."]);
        }

        $r = \App\Support\RegistroOptica::reenviarCredenciales($empresa, $admin, $licencia);

        if ($r['enviado']) {
            return back()->with('success', "Credenciales enviadas a {$r['correo']}. El administrador entra con {$r['usuario']} y debe cambiar la contraseña temporal.");
        }

        return back()->with('success', "No se pudo enviar a: {$r['error']}. (Si había dos correos, el otro sí lo recibió.) Entrégale estos datos al administrador: usuario {$r['usuario']}, contraseña temporal {$r['password']}");
    }

    public function cambiarEstadoUsuario(string $nit, string $documento): RedirectResponse
    {
        $usuario = Usuario::where('nit_empresa', $nit)->where('documento', $documento)->firstOrFail();

        if ($usuario->esSuperadmin()) {
            abort(404);
        }

        $nombre = trim($usuario->nombres . ' ' . $usuario->apellido);

        if (\App\Support\BloqueoUsuarios::estaInactivo($usuario)) {
            $correo = \App\Support\BloqueoUsuarios::activar($usuario, 'Usuario activado por VisiOptica');

            return back()->with('success', "{$nombre} fue activado y ya puede iniciar sesión."
                . ($correo ? ' Se le envió un correo avisándole.' : ' (No se pudo enviar el correo de aviso.)'));
        }

        \App\Support\BloqueoUsuarios::inactivar($usuario, 'Usuario desactivado por VisiOptica');

        return back()->with('success', "{$nombre} fue desactivado.");
    }

    protected function reglasEmpresa(?Empresa $empresa = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('empresa', 'slug')->ignore($empresa?->nit, 'nit')],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'ciudad' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function mensajesVerificacion(): array
    {
        return array_merge(CamaraComercio::mensajes(), \App\Support\Nit::mensajes(), [
            'confirmo.accepted' => 'Marca la casilla para confirmar que verificaste el NIT con la Cámara de Comercio.',
        ]);
    }

    protected function nombresCampos(): array
    {
        return [
            'nit' => 'NIT', 'camara_comercio' => 'certificado de la Cámara de Comercio', 'nombre' => 'nombre', 'slug' => 'dirección web', 'email' => 'correo',
            'admin_documento' => 'documento del administrador', 'admin_nombres' => 'nombres del administrador',
            'admin_email' => 'correo del administrador', 'admin_password' => 'contraseña',
            'admin_telefono' => 'celular del administrador',
        ];
    }
}
