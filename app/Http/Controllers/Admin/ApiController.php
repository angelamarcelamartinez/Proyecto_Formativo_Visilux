<?php

// Carpeta (espacio de nombres) donde vive este controlador: los controladores del panel del administrador.
namespace App\Http\Controllers\Admin;

// Controlador base de Laravel del que heredan todos los controladores.
use App\Http\Controllers\Controller;
// Alcance: filtra las consultas para que cada administrador vea solo los datos de SU óptica (por nit_empresa).
use App\Support\Alcance;
// Carbon: librería de fechas; la usamos para mostrar las fechas en formato dd/mm/aaaa.
use Illuminate\Support\Carbon;
// DB: permite hacer consultas a la base de datos (DB::table('...')->...).
use Illuminate\Support\Facades\DB;
// Schema: permite preguntar si una tabla existe en la base de datos.
use Illuminate\Support\Facades\Schema;
// View: el tipo de dato que devuelven los métodos que muestran una pantalla.
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
// Declaramos la clase ApiController, que hereda de Controller.
class ApiController extends Controller
{
    /** Pantalla con las APIs externas: carga los archivos de public/apis/. */
    // Se ejecuta al entrar a /admin/api (ruta admin.api.index). Devuelve una vista.
    public function index(): View
    {
        // Lista de las APIs que se muestran: cada una con su archivo, el título de la pestaña y de dónde salen los datos.
        $apis = [
            // API 1: departamentos de Colombia.
            ['archivo' => 'api_departamentos.php', 'titulo' => 'Departamentos de Colombia', 'fuente' => 'api-colombia.com'],
            // API 2: índice UV de Ibagué.
            ['archivo' => 'api_indice_uv.php', 'titulo' => 'Índice UV de Ibagué', 'fuente' => 'api.open-meteo.com'],
            // API 3: festivos de Colombia.
            ['archivo' => 'api_festivos.php', 'titulo' => 'Festivos de Colombia', 'fuente' => 'date.nager.at'],
        // Cierra la lista.
        ];

        // Muestra resources/views/admin/api/index.blade.php y le manda la lista con el nombre $apis.
        return view('admin.api.index', ['apis' => $apis]);
    // Fin del método index.
    }

    /** Nombre legible de un estado (por si la tabla `estado` no trae alguno de los ids de cita). */
    // Recibe el número del estado ($id, puede ser null) y la lista de nombres de la tabla estado; devuelve el nombre en texto.
    protected function nombreEstado(?int $id, $nombres): string
    {
        // Nombres fijos para los estados más usados (11 = Confirmada no está en la tabla estado del dump).
        $fijos = [1 => 'Activo', 2 => 'Inactivo', 3 => 'Pendiente', 4 => 'Cancelada', 5 => 'Completada', 11 => 'Confirmada'];

        // Primero busca en los fijos; si no está, en la tabla estado; si tampoco, devuelve 'Sin estado'. (?? = "si no existe, usa lo de la derecha")
        return $fijos[$id] ?? ($nombres[$id] ?? 'Sin estado');
    // Fin del método nombreEstado.
    }

    /** Pantalla "Triggers": lo que dejan los 4 triggers en sus tablas de registro (si el SQL aún no se ejecutó, salen vacías). */
    // Se ejecuta al entrar a /admin/triggers (ruta admin.triggers.index).
    public function triggers(): View
    {
        // Función corta que dice si una tabla existe (true/false). Así no falla si todavía no se ejecutó el SQL de triggers.
        $existe = fn (string $t) => Schema::hasTable($t);
        // Trae toda la tabla estado como lista [id_estado => nom_estado], por ejemplo [10 => 'Suspendido'].
        $estados = DB::table('estado')->pluck('nom_estado', 'id_estado');

        // Cambios de cita: con el nombre del paciente, el día de la cita y los estados escritos en palabras.
        // Empieza como una colección vacía (por si la tabla no existe).
        $logCitas = collect();
        // Solo si existe la tabla que llena el trigger 2 (trg_cita_cambio_estado)...
        if ($existe('log_cambio_cita')) {
            // ...trae los 5 últimos registros de esa tabla y recorre cada uno ($r) para completarlo.
            $logCitas = $this->ultimos('log_cambio_cita', 'fecha_hora')->map(function ($r) use ($estados) {
                // Busca la cita en asignacion_cita con el id guardado por el trigger, para saber su fecha y hora.
                $cita = DB::table('asignacion_cita')->where('id_cita', $r->id_cita)->first(['fecha_cita', 'hora_cita']);
                // Busca al paciente en usuario con el documento guardado por el trigger, para saber su nombre.
                $pac = DB::table('usuario')->where('documento', $r->id_usuario)->first(['nombres', 'apellido']);

                // Devuelve un objeto con los datos ya listos para mostrar.
                return (object) [
                    // Nombre completo del paciente; si no se encontró, "Paciente 12345".
                    'paciente' => $pac ? trim($pac->nombres.' '.$pac->apellido) : 'Paciente '.$r->id_usuario,
                    // Documento del paciente (lo guardó el trigger en id_usuario).
                    'documento' => $r->id_usuario,
                    // Número de la cita (lo guardó el trigger).
                    'id_cita' => $r->id_cita,
                    // Fecha de la cita en dd/mm/aaaa + la hora en HH:MM (substr corta los segundos); null si la cita ya no existe.
                    'cita' => $cita ? Carbon::parse($cita->fecha_cita)->format('d/m/Y').' '.substr((string) $cita->hora_cita, 0, 5) : null,
                    // Estado ANTES del cambio, en palabras (el trigger guardó OLD.id_estado).
                    'de' => $this->nombreEstado($r->estado_anterior, $estados),
                    // Estado DESPUÉS del cambio, en palabras (el trigger guardó NEW.id_estado).
                    'a' => $this->nombreEstado($r->estado_nuevo, $estados),
                    // Cuándo se hizo el cambio (la fecha la puso MySQL), en formato dd/mm/aaaa hh:mm am/pm.
                    'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
                // Cierra el objeto.
                ];
            // Cierra la función del map.
            });
        // Cierra el if.
        }

        // Cambios de estado de usuarios (trigger 3). Empieza vacía.
        $logUsuarios = collect();
        // Solo si existe la tabla log_estado_usuario...
        if ($existe('log_estado_usuario')) {
            // ...trae los 5 últimos y arma cada registro (fn = función corta de una sola expresión).
            $logUsuarios = $this->ultimos('log_estado_usuario', 'fecha_hora')->map(fn ($r) => (object) [
                // Nombre del usuario (el trigger ya lo guardó armado).
                'nombre' => $r->nombre,
                // Documento del usuario.
                'documento' => $r->documento,
                // Estado anterior en palabras (por ejemplo Activo).
                'de' => $this->nombreEstado($r->estado_anterior, $estados),
                // Estado nuevo en palabras (por ejemplo Inactivo).
                'a' => $this->nombreEstado($r->estado_nuevo, $estados),
                // Fecha y hora del cambio en formato legible.
                'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
            // Cierra el objeto y el map.
            ]);
        // Cierra el if.
        }

        // Muestra resources/views/admin/triggers/index.blade.php y le manda las 4 listas.
        return view('admin.triggers.index', [
            // Trigger 1 (accesos): si existe log_acceso, trae los 5 últimos con nombre, documento y fecha; si no, lista vacía.
            'logAcceso' => $existe('log_acceso') ? $this->ultimos('log_acceso', 'fecha_hora')->map(fn ($r) => (object) [
                // Datos de cada acceso: quién y cuándo inició sesión.
                'nombre' => $r->nombre, 'documento' => $r->documento, 'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
            // Si la tabla no existe, manda una colección vacía.
            ]) : collect(),
            // Trigger 2: la lista de cambios de citas que armamos arriba.
            'logCitas' => $logCitas,
            // Trigger 3: la lista de cambios de usuarios que armamos arriba.
            'logUsuarios' => $logUsuarios,
            // Trigger 4: cambios de precio de los productos (precio anterior → precio nuevo).
            'logPrecios' => $existe('log_precio_producto') ? $this->ultimos('log_precio_producto', 'fecha_hora')->map(fn ($r) => (object) [
                // Nombre del producto; si viene vacío, "Producto #5".
                'producto' => $r->producto ?: 'Producto #'.$r->id_producto,
                // Precio anterior con formato de pesos ($180.000); si no tenía precio, "sin precio".
                'de' => $r->precio_anterior === null ? 'sin precio' : '$'.number_format((float) $r->precio_anterior, 0, ',', '.'),
                // Precio nuevo con el mismo formato.
                'a' => $r->precio_nuevo === null ? 'sin precio' : '$'.number_format((float) $r->precio_nuevo, 0, ',', '.'),
                // Fecha y hora del cambio de precio.
                'fecha_hora' => Carbon::parse($r->fecha_hora)->format('d/m/Y h:i a'),
            // Si la tabla no existe, lista vacía.
            ]) : collect(),
        // Cierra los datos que se mandan a la vista.
        ]);
    // Fin del método triggers.
    }

    /** Últimos 5 registros de una tabla de log de esta óptica. */
    // Recibe el nombre de la tabla y la columna por la que se ordena (fecha_hora).
    protected function ultimos(string $tabla, string $orden)
    {
        // SELECT * FROM $tabla WHERE nit_empresa = (mi óptica) ORDER BY fecha_hora DESC LIMIT 5
        return Alcance::restringir(DB::table($tabla), $tabla)->orderByDesc($orden)->limit(5)->get();
    // Fin del método ultimos.
    }
// Fin de la clase.
}
