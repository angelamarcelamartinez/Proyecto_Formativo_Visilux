<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Alcance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Secciones "API" y "Triggers" del panel del administrador.
 *
 * API: la pantalla /admin/api solo MUESTRA los archivos de public/apis/ (cada uno consume una API
 * externa pública con cURL y pinta su tabla). La lógica de las APIs vive únicamente en esos archivos,
 * así no está duplicada aquí:
 *   1. public/apis/api_departamentos.php  · api-colombia.com
 *   2. public/apis/api_indice_uv.php      · api.open-meteo.com
 *   3. public/apis/api_festivos.php       · date.nager.at
 * Rutas: /admin/api (pantalla) y /admin/triggers.
 */
class ApiController extends Controller
{
    /** Pantalla con las APIs externas: carga los archivos de public/apis/. */
    public function index(): View
    {
        $apis = [
            ['archivo' => 'api_departamentos.php', 'titulo' => 'Departamentos de Colombia', 'fuente' => 'api-colombia.com'],
            ['archivo' => 'api_indice_uv.php', 'titulo' => 'Índice UV de Ibagué', 'fuente' => 'api.open-meteo.com'],
            ['archivo' => 'api_festivos.php', 'titulo' => 'Festivos de Colombia', 'fuente' => 'date.nager.at'],
        ];

        return view('admin.api.index', ['apis' => $apis]);
    }

    /** Nombre legible de un estado (por si la tabla `estado` no trae alguno de los ids de cita). */
    protected function nombreEstado(?int $id, $nombres): string
    {
        $fijos = [1 => 'Activo', 2 => 'Inactivo', 3 => 'Pendiente', 4 => 'Cancelada', 5 => 'Completada', 11 => 'Confirmada'];

        return $fijos[$id] ?? ($nombres[$id] ?? 'Sin estado');
    }

    /** Pantalla "Triggers": lo que dejan los 4 triggers en sus tablas de registro (si el SQL aún no se ejecutó, salen vacías). */
    public function triggers(): View
    {
        $existe = fn (string $t) => Schema::hasTable($t);
        $estados = DB::table('estado')->pluck('nom_estado', 'id_estado');

        // Cambios de cita: con el nombre del paciente, el día de la cita y los estados escritos en palabras.
        $logCitas = collect();
        if ($existe('log_cambio_cita')) {
            $logCitas = $this->ultimos('log_cambio_cita', 'fecha_hora')->map(function ($r) use ($estados) {
                $cita = DB::table('asignacion_cita')->where('id_cita', $r->id_cita)->first(['fecha_cita', 'hora_cita']);
                $pac = DB::table('usuario')->where('documento', $r->id_usuario)->first(['nombres', 'apellido']);

                return (object) [
                    'paciente' => $pac ? trim($pac->nombres.' '.$pac->apellido) : 'Paciente '.$r->id_usuario,
                    'documento' => $r->id_usuario,
                    'id_cita' => $r->id_cita,
                    'cita' => $cita ? Carbon::parse($cita->fecha_cita)->format('d/m/Y').' '.substr((string) $cita->hora_cita, 0, 5) : null,
                    'de' => $this->nombreEstado($r->estado_anterior, $estados),
                    'a' => $this->nombreEstado($r->estado_nuevo, $estados),
                    'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
                ];
            });
        }

        $logUsuarios = collect();
        if ($existe('log_estado_usuario')) {
            $logUsuarios = $this->ultimos('log_estado_usuario', 'fecha_hora')->map(fn ($r) => (object) [
                'nombre' => $r->nombre,
                'documento' => $r->documento,
                'de' => $this->nombreEstado($r->estado_anterior, $estados),
                'a' => $this->nombreEstado($r->estado_nuevo, $estados),
                'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
            ]);
        }

        return view('admin.triggers.index', [
            'logAcceso' => $existe('log_acceso') ? $this->ultimos('log_acceso', 'fecha_hora')->map(fn ($r) => (object) [
                'nombre' => $r->nombre, 'documento' => $r->documento, 'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
            ]) : collect(),
            'logCitas' => $logCitas,
            'logUsuarios' => $logUsuarios,
            // Trigger 4: cambios de precio de los productos (precio anterior → precio nuevo).
            'logPrecios' => $existe('log_precio_producto') ? $this->ultimos('log_precio_producto', 'fecha_hora')->map(fn ($r) => (object) [
                'producto' => $r->producto ?: 'Producto #'.$r->id_producto,
                'de' => $r->precio_anterior === null ? 'sin precio' : '$'.number_format((float) $r->precio_anterior, 0, ',', '.'),
                'a' => $r->precio_nuevo === null ? 'sin precio' : '$'.number_format((float) $r->precio_nuevo, 0, ',', '.'),
                'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
            ]) : collect(),
        ]);
    }

    /** Últimos 5 registros de una tabla de log de esta óptica. */
    protected function ultimos(string $tabla, string $orden)
    {
        return Alcance::restringir(DB::table($tabla), $tabla)->orderByDesc($orden)->limit(5)->get();
    }
}
