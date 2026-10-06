<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\PaginaEmpresa;
use App\Models\Plan;
use App\Models\Usuario;
use App\Support\Licencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        return view('superadmin.empresas.index', compact('opticas', 'estado', 'q', 'conteos'));
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
            ->get(['documento', 'nombres', 'apellido', 'email', 'ultimo_acceso']);

        $inicioMes = now()->startOfMonth();

        $movimiento = [
            'citas_total' => DB::table('asignacion_cita')->where('nit_empresa', $nit)->count(),
            'citas_mes' => DB::table('asignacion_cita')->where('nit_empresa', $nit)->where('fecha_cita', '>=', $inicioMes)->count(),
            'ventas_total' => (float) DB::table('venta')->where('nit_empresa', $nit)->sum('total')
                + (float) DB::table('venta_medicamento')->where('nit_empresa', $nit)->sum('total'),
            'ventas_mes' => (float) DB::table('venta')->where('nit_empresa', $nit)->where('fecha_venta', '>=', $inicioMes)->sum('total')
                + (float) DB::table('venta_medicamento')->where('nit_empresa', $nit)->where('fecha', '>=', $inicioMes)->sum('total'),
            'historias' => DB::table('historia_clinica')->where('nit_empresa', $nit)->count(),
            'productos' => DB::table('producto')->where('nit_empresa', $nit)->count(),
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

    public function create(): View
    {
        return view('superadmin.empresas.form', [
            'empresa' => new Empresa(),
            'modo' => 'crear',
        ]);
    }

    /**
     * Registra una óptica nueva con su administrador y el mes de prueba gratis.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge($this->reglasEmpresa(), [
            'nit' => ['required', 'string', 'max:20', 'unique:empresa,nit'],
            'admin_documento' => ['required', 'integer', 'min:1', 'unique:usuario,documento'],
            'admin_nombres' => ['required', 'string', 'max:100'],
            'admin_apellido' => ['nullable', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:100', 'unique:usuario,email'],
            'admin_telefono' => ['required', 'string', 'max:20', 'unique:usuario,telefono'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]), [], $this->nombresCampos());

        $empresa = DB::transaction(function () use ($data) {
            $empresa = Empresa::create([
                'nit' => $data['nit'],
                'nombre' => $data['nombre'],
                'slug' => Empresa::slugDisponible($data['slug'] ?: $data['nombre']),
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'ciudad' => $data['ciudad'] ?? null,
                'estado' => 'activa',
                'prueba_usada' => 0,
                'fecha_registro' => now(),
            ]);

            Usuario::create([
                'documento' => $data['admin_documento'],
                'nombres' => $data['admin_nombres'],
                'apellido' => $data['admin_apellido'] ?? '',
                'telefono' => $data['admin_telefono'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'fecha_creacion' => now(),
                'id_rol' => config('visioptica.rol_admin'),
                'id_tipo_docu' => 1,
                'id_estado' => 1,
                'nit_empresa' => $empresa->nit,
            ]);

            $prueba = Plan::where('es_prueba', 1)->where('activo', 1)->first();
            if ($prueba) {
                Licencias::asignar($empresa, $prueba, 'PRUEBA', 0, 'Mes de prueba al registrar la óptica');
            }

            PaginaEmpresa::deEmpresa($empresa);

            Actividad::registrar($empresa->nit, 'crear', "Óptica {$empresa->nombre} registrada", 'empresa', $empresa->nit);

            return $empresa;
        });

        return redirect()->route('superadmin.empresas.show', $empresa->nit)
            ->with('success', "La óptica {$empresa->nombre} quedó registrada con 1 mes de prueba.");
    }

    public function edit(string $nit): View
    {
        return view('superadmin.empresas.form', [
            'empresa' => Empresa::findOrFail($nit),
            'modo' => 'editar',
        ]);
    }

    public function update(Request $request, string $nit): RedirectResponse
    {
        $empresa = Empresa::findOrFail($nit);

        $data = $request->validate($this->reglasEmpresa($empresa), [], $this->nombresCampos());

        $empresa->update([
            'nombre' => $data['nombre'],
            'slug' => Empresa::slugDisponible($data['slug'] ?: $data['nombre'], $empresa->nit),
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
        ]);

        Actividad::registrar($empresa->nit, 'editar', 'Datos de la óptica actualizados', 'empresa', $empresa->nit);

        return redirect()->route('superadmin.empresas.show', $empresa->nit)
            ->with('success', 'Datos de la óptica actualizados.');
    }

    /**
     * Activar o suspender una óptica. Suspendida no puede entrar a su panel
     * y su página pública deja de mostrarse.
     */
    public function cambiarEstado(Request $request, string $nit): RedirectResponse
    {
        $empresa = Empresa::findOrFail($nit);

        if ($empresa->estaPendiente()) {
            return back()->withErrors(['general' => 'Esta óptica todavía espera la aprobación de su pago. Apruébala o recházala en Licencias y pagos.']);
        }

        $nuevo = $empresa->estaSuspendida() ? 'activa' : 'suspendida';

        $empresa->update(['estado' => $nuevo]);

        Actividad::registrar(
            $empresa->nit, 'licencia',
            $nuevo === 'activa' ? 'Óptica reactivada por el superadmin' : 'Óptica suspendida por el superadmin',
            'empresa', $empresa->nit
        );

        return back()->with('success', $nuevo === 'activa'
            ? "{$empresa->nombre} está activa de nuevo."
            : "{$empresa->nombre} quedó suspendida. Su equipo ya no puede entrar al panel.");
    }

    /**
     * Asignar o renovar un plan directamente (pago recibido por fuera del sistema).
     */
    public function asignarLicencia(Request $request, string $nit): RedirectResponse
    {
        $empresa = Empresa::findOrFail($nit);

        if ($empresa->estaPendiente()) {
            return back()->withErrors(['general' => 'Primero aprueba el pago de su registro en Licencias y pagos.']);
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

    protected function nombresCampos(): array
    {
        return [
            'nit' => 'NIT', 'nombre' => 'nombre', 'slug' => 'dirección web', 'email' => 'correo',
            'admin_documento' => 'documento del administrador', 'admin_nombres' => 'nombres del administrador',
            'admin_email' => 'correo del administrador', 'admin_password' => 'contraseña',
            'admin_telefono' => 'celular del administrador',
        ];
    }
}
