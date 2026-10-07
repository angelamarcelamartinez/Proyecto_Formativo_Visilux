<?php

namespace App\Http\Controllers\Admin;

use App\Support\Alcance;
use App\Http\Controllers\Controller;
use App\Support\SimpleXlsx;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD genérico: una sola clase sabe leer/crear/editar/borrar
 * cualquiera de las 48 tablas descritas en config/admin_tables.php.
 *
 * Opciones extra que se pueden poner en config/admin_tables.php
 * (todas son opcionales; si no están, la tabla funciona como antes):
 *
 *  Por tabla:
 *   - list_columns    : columnas que se ven en el listado; el resto se ve con "Ver más".
 *   - joins           : tablas relacionadas para mostrar/buscar datos (ej. datos del paciente).
 *   - virtual_columns : columnas calculadas a partir de los joins (solo lectura).
 *   - filters         : columnas por las que se puede filtrar con listas desplegables o fecha.
 *   - export          : true para mostrar el botón "Excel".
 *
 *  Por columna:
 *   - on_create   : 'now' o 'today' → el sistema la llena al crear; no aparece en el formulario.
 *   - in_form     : false → solo se ve en la tabla, nunca en el formulario.
 *   - upload_dir  : carpeta (dentro de public) donde se guardan los archivos subidos.
 *   - pk_editable : true → la llave primaria manual se puede cambiar al editar (se
 *                   actualiza también en las tablas que la usan).
 *   - lookup      : 'usuario' → se escribe el documento y se traen los datos del usuario.
 *   - list_raw    : true → en la tabla se muestra el valor guardado y no la etiqueta de la FK.
 *   - default     : valor sugerido al crear (para fechas, un texto como '+1 year').
 *   - rules       : reglas de validación adicionales de Laravel.
 */
class CrudController extends Controller
{
    /** Columnas que a partir de este número se resumen con el botón "Ver más". */
    protected const MAX_LIST_COLUMNS = 6;

    protected function tableConfig(string $table): array
    {
        $config = config("admin_tables.tables.{$table}");

        if (! $config || Alcance::oculta($table)) {
            throw new NotFoundHttpException("La tabla «{$table}» no está registrada en el panel.");
        }

        // Se ajusta la configuración a la estructura REAL de la base de datos:
        // se quitan columnas que no existen (ej. si falta ejecutar un script SQL)
        // y se guardan tipo, largo máximo y rango de cada columna para validar.
        $dbColumns = $this->dbColumns($table);
        if (! empty($dbColumns)) {
            $config['columns'] = array_values(array_filter(
                array_map(function ($col) use ($dbColumns) {
                    if (! isset($dbColumns[$col['name']])) {
                        return null;
                    }
                    $col['db'] = $dbColumns[$col['name']];

                    return $col;
                }, $config['columns'])
            ));
        }

        return $config;
    }

    /**
     * Estructura de las columnas de una tabla según MySQL (information_schema).
     */
    protected function dbColumns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }

        try {
            $rows = DB::select(
                'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE
                 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );
        } catch (\Throwable $e) {
            return $cache[$table] = [];
        }

        $cols = [];
        foreach ($rows as $r) {
            $r = (array) array_change_key_case((array) $r, CASE_UPPER);
            $unsigned = str_contains($r['COLUMN_TYPE'], 'unsigned');
            $info = ['type' => $r['DATA_TYPE']];

            switch ($r['DATA_TYPE']) {
                case 'varchar':
                case 'char':
                    $info['max_length'] = (int) $r['CHARACTER_MAXIMUM_LENGTH'];
                    break;
                case 'tinyint':
                    [$info['min'], $info['max']] = $unsigned ? [0, 255] : [-128, 127];
                    break;
                case 'smallint':
                    [$info['min'], $info['max']] = $unsigned ? [0, 65535] : [-32768, 32767];
                    break;
                case 'int':
                    [$info['min'], $info['max']] = $unsigned ? [0, 4294967295] : [-2147483648, 2147483647];
                    break;
                case 'decimal':
                    $entero = (int) $r['NUMERIC_PRECISION'] - (int) $r['NUMERIC_SCALE'];
                    $escala = (int) $r['NUMERIC_SCALE'];
                    $info['max'] = (float) (str_repeat('9', max($entero, 1)).($escala ? '.'.str_repeat('9', $escala) : ''));
                    $info['min'] = $unsigned ? 0 : -$info['max'];
                    $info['step'] = $escala ? '0.'.str_repeat('0', $escala - 1).'1' : '1';
                    break;
            }

            $cols[$r['COLUMN_NAME']] = $info;
        }

        return $cache[$table] = $cols;
    }

    /* =====================================================================
     |  Etiquetas legibles para las llaves foráneas
     |  (ej. "1104939292 - Juanita Ramirez" en vez de solo el documento)
     * ===================================================================== */

    /**
     * Consulta que devuelve, para cada registro de la tabla referenciada,
     * su valor (v) y una etiqueta fácil de reconocer (l).
     */
    protected function labelQuery(string $fkTable, string $fkColumn, string $fkDisplay): Builder
    {
        $pac = "CONCAT(u.documento, ' - ', u.nombres, ' ', u.apellido)";

        $consulta = match ($fkTable) {
            'usuario' => DB::table('usuario')
                ->selectRaw("usuario.{$fkColumn} as v, CONCAT(usuario.documento, ' - ', usuario.nombres, ' ', usuario.apellido) as l")
                ->orderBy('usuario.nombres'),

            'optometra' => DB::table('optometra')
                ->selectRaw("optometra.{$fkColumn} as v, CONCAT(optometra.nombre, ' ', optometra.apellido) as l")
                ->orderBy('optometra.nombre'),

            'formula_medica' => DB::table('formula_medica')
                ->leftJoin('usuario as u', 'u.documento', '=', 'formula_medica.documento_paciente')
                ->selectRaw("formula_medica.id_formula as v, CONCAT({$pac}, ' · Fórmula #', formula_medica.id_formula, IF(formula_medica.fecha_formula IS NULL, '', CONCAT(' (', DATE_FORMAT(formula_medica.fecha_formula, '%Y-%m-%d'), ')'))) as l")
                ->orderByDesc('formula_medica.id_formula'),

            'asignacion_cita' => DB::table('asignacion_cita')
                ->leftJoin('usuario as u', 'u.documento', '=', 'asignacion_cita.id_usuario')
                ->selectRaw("asignacion_cita.id_cita as v, CONCAT(DATE_FORMAT(asignacion_cita.fecha_cita, '%Y-%m-%d'), ' ', TIME_FORMAT(asignacion_cita.hora_cita, '%H:%i'), ' · ', {$pac}) as l")
                ->orderByDesc('asignacion_cita.fecha_cita'),

            'diagnostico' => DB::table('diagnostico')
                ->leftJoin('enfermedades as e', 'e.id_enfermedad', '=', 'diagnostico.id_enfermedad')
                ->leftJoin('formula_medica as f', 'f.id_formula', '=', 'diagnostico.id_formula')
                ->leftJoin('usuario as u', 'u.documento', '=', 'f.documento_paciente')
                ->selectRaw("diagnostico.id_diagnostico as v, CONCAT(COALESCE(e.nombre_enfer, 'Sin enfermedad'), ' (', diagnostico.ojo, ')', IF(u.documento IS NULL, '', CONCAT(' · ', {$pac}))) as l")
                ->orderBy('e.nombre_enfer'),

            'historia_clinica' => DB::table('historia_clinica')
                ->leftJoin('asignacion_cita as c', 'c.id_cita', '=', 'historia_clinica.id_asignacioncita')
                ->leftJoin('usuario as u', 'u.documento', '=', 'c.id_usuario')
                ->selectRaw("historia_clinica.id_historia_clinica as v, CONCAT('#', historia_clinica.id_historia_clinica, ' · ', DATE_FORMAT(historia_clinica.fecha_registro, '%Y-%m-%d'), IF(u.documento IS NULL, '', CONCAT(' · ', {$pac}))) as l")
                ->orderByDesc('historia_clinica.id_historia_clinica'),

            'venta' => DB::table('venta')
                ->leftJoin('usuario as u', 'u.documento', '=', 'venta.id_usuario')
                ->selectRaw("venta.id_venta as v, CONCAT('Venta #', venta.id_venta, ' · ', DATE_FORMAT(venta.fecha_venta, '%Y-%m-%d'), ' · ', COALESCE({$pac}, '')) as l")
                ->orderByDesc('venta.id_venta'),

            'venta_medicamento' => DB::table('venta_medicamento')
                ->leftJoin('usuario as u', 'u.documento', '=', 'venta_medicamento.id_usuario')
                ->selectRaw("venta_medicamento.id_venta_med as v, CONCAT('Venta #', venta_medicamento.id_venta_med, ' · ', DATE_FORMAT(venta_medicamento.fecha, '%Y-%m-%d'), ' · ', COALESCE({$pac}, '')) as l")
                ->orderByDesc('venta_medicamento.id_venta_med'),

            'carrito' => DB::table('carrito')
                ->leftJoin('usuario as u', 'u.documento', '=', 'carrito.id_usuario')
                ->selectRaw("carrito.id_carrito as v, CONCAT('Carrito #', carrito.id_carrito, ' · ', COALESCE({$pac}, '')) as l")
                ->orderByDesc('carrito.id_carrito'),

            'pedido' => DB::table('pedido')
                ->leftJoin('proveedor as p', 'p.nit', '=', 'pedido.id_proveedor')
                ->selectRaw("pedido.id_pedido as v, CONCAT('Pedido #', pedido.id_pedido, ' · ', DATE_FORMAT(pedido.fecha_pedido, '%Y-%m-%d'), ' · ', COALESCE(p.nombre_prov, '')) as l")
                ->orderByDesc('pedido.id_pedido'),

            'envio' => DB::table('envio')
                ->selectRaw("envio.id_envio as v, CONCAT('Envío #', envio.id_envio, ' · Venta #', envio.id_venta, ' · ', envio.ciudad) as l")
                ->orderByDesc('envio.id_envio'),

            'agendamiento_procedimiento' => DB::table('agendamiento_procedimiento')
                ->selectRaw("agendamiento_procedimiento.id_agendamiento_procedimiento as v, CONCAT('#', agendamiento_procedimiento.id_agendamiento_procedimiento, ' · ', DATE_FORMAT(agendamiento_procedimiento.fecha_inicio, '%Y-%m-%d %H:%i'), ' · ', agendamiento_procedimiento.tipo) as l")
                ->orderByDesc('agendamiento_procedimiento.fecha_inicio'),

            'detalle_prov_prod' => DB::table('detalle_prov_prod')
                ->leftJoin('proveedor as p', 'p.nit', '=', 'detalle_prov_prod.nit_prov')
                ->leftJoin('producto as pr', 'pr.id_producto', '=', 'detalle_prov_prod.id_producto')
                ->selectRaw("detalle_prov_prod.id_proveedor_producto as v, CONCAT(COALESCE(p.nombre_prov, ''), ' - ', COALESCE(pr.nombre_producto, '')) as l")
                ->orderBy('p.nombre_prov'),

            default => DB::table($fkTable)
                ->selectRaw("`{$fkTable}`.`{$fkColumn}` as v, `{$fkTable}`.`{$fkDisplay}` as l")
                ->orderBy("{$fkTable}.{$fkDisplay}"),
        };

        // Solo se ofrecen registros de la propia óptica (y nunca el rol Superadmin).
        if ($fkTable === 'rol') {
            $consulta->where('rol.id_rol', '<>', (int) config('visioptica.rol_superadmin'));
        }

        return Alcance::restringir($consulta, $fkTable);
    }

    /**
     * Todas las opciones (valor => etiqueta) de una llave foránea, para los selects.
     */
    protected function fkOptions(array $col): array
    {
        return $this->labelQuery($col['fk_table'], $col['fk_column'], $col['fk_display'])
            ->limit(2000)
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->v => $row->l])
            ->all();
    }

    /**
     * Solo las etiquetas de los valores que aparecen en la página actual.
     */
    protected function fkLabels(array $col, array $values): array
    {
        $values = array_values(array_unique(array_filter($values, fn ($v) => $v !== null && $v !== '')));
        if (empty($values)) {
            return [];
        }

        $query = $this->labelQuery($col['fk_table'], $col['fk_column'], $col['fk_display']);
        $query->whereIn("{$col['fk_table']}.{$col['fk_column']}", $values);

        return $query->get()->mapWithKeys(fn ($row) => [(string) $row->v => $row->l])->all();
    }

    /* =====================================================================
     |  Columnas: cuáles se listan, cuáles van en "Ver más", cuáles en el formulario
     * ===================================================================== */

    /** Todas las columnas "mostrables": las reales (menos contraseñas) y las virtuales. */
    protected function allDisplayColumns(array $config): array
    {
        $cols = [];
        foreach ($config['columns'] as $col) {
            if ($col['type'] === 'password') {
                continue;
            }
            $cols[$col['name']] = $col;
        }
        foreach ($config['virtual_columns'] ?? [] as $v) {
            $cols[$v['name']] = $v + ['type' => 'text', 'is_fk' => false, 'is_pk' => false, 'virtual' => true];
        }

        return $cols;
    }

    protected function listColumns(array $config): array
    {
        $all = $this->allDisplayColumns($config);

        if (! empty($config['list_columns'])) {
            return array_values(array_filter(array_map(fn ($n) => $all[$n] ?? null, $config['list_columns'])));
        }

        return array_slice(array_values($all), 0, self::MAX_LIST_COLUMNS);
    }

    protected function hasMoreColumns(array $config): bool
    {
        return count($this->allDisplayColumns($config)) > count($this->listColumns($config));
    }

    /* =====================================================================
     |  Consulta del listado: joins, búsqueda y filtros
     * ===================================================================== */

    protected function qualify(string $table, string $column): string
    {
        return str_contains($column, '.') ? $column : "{$table}.{$column}";
    }

    protected function baseQuery(string $table, array $config): Builder
    {
        $query = DB::table($table)->select("{$table}.*");
        Alcance::restringir($query, $table);

        foreach ($config['joins'] ?? [] as $join) {
            $query->leftJoin("{$join['table']} as {$join['as']}", $join['first'], '=', $join['second']);
        }

        foreach ($config['virtual_columns'] ?? [] as $v) {
            $query->selectRaw("{$v['select']} as `{$v['name']}`");
        }

        return $query;
    }

    /** Filtros disponibles para la tabla, con sus opciones ya cargadas. */
    protected function filterDefinitions(array $config): array
    {
        $defs = [];
        $columns = collect($config['columns'])->keyBy('name');

        foreach ($config['filters'] ?? [] as $name) {
            $col = $columns->get($name);
            if (! $col) {
                continue;
            }

            if (in_array($col['type'], ['date', 'datetime-local'], true)) {
                $defs[] = ['name' => $name, 'label' => $col['label'], 'kind' => 'date', 'type' => $col['type']];
            } elseif ($col['is_fk']) {
                $defs[] = ['name' => $name, 'label' => $col['label'], 'kind' => 'select', 'options' => $this->fkOptions($col)];
            } elseif (! empty($col['options'])) {
                $defs[] = ['name' => $name, 'label' => $col['label'], 'kind' => 'select', 'options' => array_combine($col['options'], $col['options'])];
            }
        }

        return $defs;
    }

    protected function applySearchAndFilters(Builder $query, string $table, array $config, Request $request): void
    {
        $q = trim((string) $request->get('q', ''));
        if ($q !== '' && ! empty($config['search_columns'])) {
            // Cada palabra se busca por separado: así "Juanita Ramirez" encuentra
            // a la persona aunque el nombre y el apellido estén en columnas distintas.
            $palabras = array_slice(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY), 0, 6);
            foreach ($palabras as $palabra) {
                $like = '%'.addcslashes($palabra, '%_\\').'%';
                $query->where(function ($sub) use ($config, $table, $like) {
                    foreach ($config['search_columns'] as $col) {
                        $sub->orWhere($this->qualify($table, $col), 'like', $like);
                    }
                });
            }
        }

        $columns = collect($config['columns'])->keyBy('name');
        foreach ($config['filters'] ?? [] as $name) {
            $value = $request->get('f_'.$name);
            $col = $columns->get($name);
            if ($value === null || $value === '' || ! $col) {
                continue;
            }

            if (in_array($col['type'], ['date', 'datetime-local'], true)) {
                $query->whereDate("{$table}.{$name}", $value);
            } else {
                $query->where("{$table}.{$name}", $value);
            }
        }
    }

    /** Etiquetas de las FK para un conjunto de filas. */
    protected function fkMapsFor(array $config, iterable $rows): array
    {
        $fkMaps = [];
        foreach ($config['columns'] as $col) {
            if ($col['is_fk']) {
                $values = [];
                foreach ($rows as $row) {
                    $values[] = $row->{$col['name']} ?? null;
                }
                $fkMaps[$col['name']] = $this->fkLabels($col, $values);
            }
        }

        return $fkMaps;
    }

    public function index(Request $request, string $table): View|string
    {
        $config = $this->tableConfig($table);
        $pk = $config['primary_key'];

        $query = $this->baseQuery($table, $config);
        $this->applySearchAndFilters($query, $table, $config, $request);

        $rows = $query->orderByDesc("{$table}.{$pk}")->paginate(15)->withQueryString();
        // Los links de paginación siempre apuntan al listado normal (no a la respuesta parcial)
        $rows->withPath(route('admin.crud.index', $table));

        $data = [
            'table' => $table,
            'config' => $config,
            'rows' => $rows,
            'fkMaps' => $this->fkMapsFor($config, $rows->items()),
            'listColumns' => $this->listColumns($config),
            'detailColumns' => $this->allDisplayColumns($config),
            'hasMore' => $this->hasMoreColumns($config),
            'q' => trim((string) $request->get('q', '')),
        ];

        // Búsqueda en vivo: solo se devuelve la tabla, sin recargar la página
        if ($request->ajax() || $request->boolean('partial')) {
            return view('admin.crud._table', $data)->render();
        }

        return view('admin.crud.index', $data + [
            'filters' => $this->filterDefinitions($config),
            'reportStats' => $this->buildReportStats($table),
            'reportCharts' => $this->buildReportCharts($table),
        ]);
    }

    /**
     * Descarga en Excel exactamente lo que se está viendo: misma búsqueda
     * y mismos filtros, pero con todas las columnas y todas las páginas.
     */
    public function export(Request $request, string $table): Response
    {
        $config = $this->tableConfig($table);
        if (empty($config['export'])) {
            throw new NotFoundHttpException('Esta tabla no tiene reporte en Excel.');
        }

        $query = $this->baseQuery($table, $config);
        $this->applySearchAndFilters($query, $table, $config, $request);
        $rows = $query->orderByDesc("{$table}.{$config['primary_key']}")->limit(20000)->get();

        $columns = array_values($this->allDisplayColumns($config));
        $fkMaps = $this->fkMapsFor($config, $rows);

        $headers = array_map(fn ($c) => $c['label'], $columns);
        $data = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $this->plainValue($col, $row, $fkMaps);
            }
            $data[] = $line;
        }

        $fileName = Str::slug($config['label_plural']).'_'.now()->format('Y-m-d_His');

        try {
            $path = SimpleXlsx::build($config['label_plural'], $headers, $data);

            return response()->download($path, $fileName.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            // Plan B si el servidor no tiene la extensión zip: CSV que Excel abre igual
            return response()->streamDownload(function () use ($headers, $data) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers, ';');
                foreach ($data as $line) {
                    fputcsv($out, $line, ';');
                }
                fclose($out);
            }, $fileName.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
    }

    /** Valor de una celda como texto/número "limpio" para Excel. */
    protected function plainValue(array $col, object $row, array $fkMaps): mixed
    {
        $val = $row->{$col['name']} ?? null;

        if ($val === null || $val === '') {
            return '';
        }
        if (! empty($col['is_fk']) && empty($col['list_raw'])) {
            return $fkMaps[$col['name']][(string) $val] ?? $val;
        }
        if (in_array($col['type'], ['decimal'], true)) {
            return (float) $val;
        }
        if ($col['type'] === 'time') {
            return substr($val, 0, 5);
        }
        if ($col['type'] === 'datetime-local') {
            return substr($val, 0, 16);
        }

        if ($col['type'] === 'number' && is_numeric($val)) {
            return $val + 0;
        }

        return (string) $val;
    }

    /**
     * Busca un usuario por documento (para autocompletar los datos del
     * paciente en citas y fórmulas médicas).
     */
    public function buscarUsuario(Request $request): JsonResponse
    {
        $documento = trim((string) $request->get('documento', ''));
        if ($documento === '' || ! ctype_digit($documento)) {
            return response()->json(['found' => false]);
        }

        $u = DB::table('usuario')
            ->leftJoin('tipo_documento as td', 'td.id_tipo_docu', '=', 'usuario.id_tipo_docu')
            ->where('usuario.documento', $documento)
            ->tap(fn ($q) => Alcance::restringir($q, 'usuario'))
            ->first(['usuario.documento', 'usuario.nombres', 'usuario.apellido', 'usuario.telefono', 'usuario.email', 'td.nom_ti_docu']);

        if (! $u) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'documento' => $u->documento,
            'tipo_documento' => $u->nom_ti_docu,
            'nombres' => $u->nombres,
            'apellido' => $u->apellido,
            'telefono' => $u->telefono,
            'email' => $u->email,
        ]);
    }

    /**
     * Mini reporte de "cómo va el negocio" para algunas tablas clave
     * (stock, ventas, citas). Se muestra arriba del listado cuando aplica.
     */
    protected function buildReportStats(string $table): array
    {
        return match ($table) {
            'producto' => $this->reportStatsProducto(),
            'lote' => $this->reportStatsLote(),
            'venta' => $this->reportStatsVenta(),
            'asignacion_cita' => $this->reportStatsCitas(),
            default => [],
        };
    }

    /**
     * Gráficas de reporte por módulo (equivalentes a las del panel general,
     * pero enfocadas en los datos propios de cada tabla). Se dibujan con
     * Chart.js directamente en admin.crud.index cuando el arreglo no está vacío.
     */
    protected function buildReportCharts(string $table): array
    {
        return match ($table) {
            'producto' => [$this->chartProductosPorCategoria()],
            'lote' => [$this->chartStockPorCategoria()],
            'venta' => [$this->chartVentasUltimosMeses()],
            'asignacion_cita' => [$this->chartCitasEstaSemana(), $this->chartCitasPorEstado()],
            default => [],
        };
    }

    /** Consulta sobre una tabla ya limitada a la óptica del administrador (para reportes y gráficas). */
    protected function propia(string $table): Builder
    {
        return Alcance::restringir(DB::table($table), $table);
    }

    protected function chartProductosPorCategoria(): array
    {
        $datos = $this->propia('producto')
            ->join('categoria_producto', 'categoria_producto.id_categoria', '=', 'producto.id_categoria')
            ->selectRaw('categoria_producto.nombre_categoria as categoria, COUNT(*) as total')
            ->groupBy('categoria_producto.nombre_categoria')
            ->orderByDesc('total')
            ->get();

        return [
            'type' => 'donut',
            'title' => 'Productos por categoría',
            'subtitle' => 'Distribución del catálogo activo',
            'labels' => $datos->pluck('categoria')->all(),
            'values' => $datos->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function chartStockPorCategoria(): array
    {
        $datos = $this->propia('lote')
            ->join('producto', 'producto.id_producto', '=', 'lote.id_producto')
            ->join('categoria_producto', 'categoria_producto.id_categoria', '=', 'producto.id_categoria')
            ->selectRaw('categoria_producto.nombre_categoria as categoria, SUM(lote.stock_actual) as total')
            ->groupBy('categoria_producto.nombre_categoria')
            ->orderByDesc('total')
            ->get();

        return [
            'type' => 'bar',
            'title' => 'Stock por categoría',
            'subtitle' => 'Unidades disponibles actualmente',
            'labels' => $datos->pluck('categoria')->all(),
            'values' => $datos->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function chartVentasUltimosMeses(): array
    {
        $inicio = now()->startOfMonth()->subMonths(5);

        $ventas = $this->propia('venta')
            ->where('fecha_venta', '>=', $inicio->toDateString())
            ->get(['fecha_venta', 'total']);

        $meses = collect(range(0, 5))->map(fn ($i) => $inicio->copy()->addMonths($i));

        $totalesPorMes = $ventas->groupBy(fn ($v) => \Illuminate\Support\Carbon::parse($v->fecha_venta)->format('Y-m'))
            ->map(fn ($grupo) => (float) $grupo->sum('total'));

        return [
            'type' => 'line',
            'title' => 'Ventas de los últimos 6 meses',
            'subtitle' => 'Ingresos en COP por mes',
            'labels' => $meses->map(fn ($m) => ucfirst($m->translatedFormat('M')))->all(),
            'values' => $meses->map(fn ($m) => round($totalesPorMes->get($m->format('Y-m'), 0), 2))->all(),
        ];
    }

    protected function chartCitasEstaSemana(): array
    {
        $inicio = now()->startOfWeek(); // lunes
        $fin = $inicio->copy()->addDays(5); // sábado

        $citas = $this->propia('asignacion_cita')
            ->whereBetween('fecha_cita', [$inicio->toDateString(), $fin->toDateString()])
            ->get(['fecha_cita']);

        $porDia = $citas->groupBy(fn ($c) => \Illuminate\Support\Carbon::parse($c->fecha_cita)->dayOfWeekIso)
            ->map->count();

        $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        return [
            'type' => 'bar',
            'title' => 'Citas esta semana',
            'subtitle' => 'Distribución por día hábil',
            'labels' => $dias,
            'values' => collect(range(1, 6))->map(fn ($iso) => (int) $porDia->get($iso, 0))->all(),
        ];
    }

    protected function chartCitasPorEstado(): array
    {
        $hoy = now();

        $datos = $this->propia('asignacion_cita')
            ->join('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado')
            ->whereMonth('asignacion_cita.fecha_cita', $hoy->month)
            ->whereYear('asignacion_cita.fecha_cita', $hoy->year)
            ->selectRaw('estado.nom_estado as estado, COUNT(*) as total')
            ->groupBy('estado.nom_estado')
            ->orderByDesc('total')
            ->get();

        return [
            'type' => 'donut',
            'title' => 'Citas por estado',
            'subtitle' => 'Mes en curso',
            'labels' => $datos->pluck('estado')->all(),
            'values' => $datos->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function reportStatsProducto(): array
    {
        $total = $this->propia('producto')->count();
        $stockBajo = $this->propia('lote')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->distinct('id_producto')
            ->count('id_producto');
        $valorInventario = (float) ($this->propia('lote')->selectRaw('SUM(stock_actual * precio_venta) as v')->value('v') ?? 0);

        return [
            ['label' => 'Productos registrados', 'value' => number_format($total)],
            ['label' => 'Con stock bajo', 'value' => number_format($stockBajo)],
            ['label' => 'Valor total en inventario', 'value' => '$'.number_format($valorInventario)],
        ];
    }

    protected function reportStatsLote(): array
    {
        $totalUnidades = (int) ($this->propia('lote')->sum('stock_actual') ?? 0);
        $lotesBajos = $this->propia('lote')->whereColumn('stock_actual', '<=', 'stock_minimo')->count();
        $valorInventario = (float) ($this->propia('lote')->selectRaw('SUM(stock_actual * precio_venta) as v')->value('v') ?? 0);

        return [
            ['label' => 'Unidades en stock', 'value' => number_format($totalUnidades)],
            ['label' => 'Lotes con stock bajo', 'value' => number_format($lotesBajos)],
            ['label' => 'Valor total en inventario', 'value' => '$'.number_format($valorInventario)],
        ];
    }

    protected function reportStatsVenta(): array
    {
        $hoy = now();
        $ventasHoy = (float) ($this->propia('venta')->whereDate('fecha_venta', $hoy->toDateString())->sum('total') ?? 0);
        $ventasMes = (float) ($this->propia('venta')
            ->whereMonth('fecha_venta', $hoy->month)
            ->whereYear('fecha_venta', $hoy->year)
            ->sum('total') ?? 0);
        $numVentasMes = $this->propia('venta')
            ->whereMonth('fecha_venta', $hoy->month)
            ->whereYear('fecha_venta', $hoy->year)
            ->count();
        $ticketPromedio = $numVentasMes > 0 ? $ventasMes / $numVentasMes : 0;

        return [
            ['label' => 'Ventas hoy', 'value' => '$'.number_format($ventasHoy)],
            ['label' => 'Ventas este mes', 'value' => '$'.number_format($ventasMes)],
            ['label' => 'Ticket promedio (mes)', 'value' => '$'.number_format($ticketPromedio)],
        ];
    }

    protected function reportStatsCitas(): array
    {
        $hoy = now();
        $citasHoy = $this->propia('asignacion_cita')->whereDate('fecha_cita', $hoy->toDateString())->count();
        $citasMes = $this->propia('asignacion_cita')
            ->whereMonth('fecha_cita', $hoy->month)
            ->whereYear('fecha_cita', $hoy->year)
            ->count();
        $completadasMes = $this->propia('asignacion_cita')
            ->join('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado')
            ->whereMonth('asignacion_cita.fecha_cita', $hoy->month)
            ->whereYear('asignacion_cita.fecha_cita', $hoy->year)
            ->where('estado.nom_estado', 'Completado')
            ->count();

        return [
            ['label' => 'Citas hoy', 'value' => number_format($citasHoy)],
            ['label' => 'Citas este mes', 'value' => number_format($citasMes)],
            ['label' => 'Completadas este mes', 'value' => number_format($completadasMes)],
        ];
    }

    /* =====================================================================
     |  Formularios
     * ===================================================================== */

    /** ¿La columna aparece en el formulario? */
    protected function inForm(array $col, bool $isEdit): bool
    {
        if ($col['auto'] || ! empty($col['on_create']) || (isset($col['in_form']) && $col['in_form'] === false)) {
            return false;
        }

        return true;
    }

    protected function formOptions(array $config): array
    {
        $fkOptions = [];
        foreach ($config['columns'] as $col) {
            if ($col['is_fk'] && empty($col['lookup'])) {
                $fkOptions[$col['name']] = $this->fkOptions($col);
            }
        }

        return $fkOptions;
    }

    /** Los catálogos compartidos por todas las ópticas no se modifican desde el panel de una óptica. */
    protected function exigirEscritura(string $table): void
    {
        if (Alcance::soloLectura($table)) {
            throw new NotFoundHttpException('Este catálogo es compartido y solo se puede consultar.');
        }
    }

    public function create(string $table): View
    {
        $this->exigirEscritura($table);
        $config = $this->tableConfig($table);

        return view('admin.crud.form', [
            'table' => $table,
            'config' => $config,
            'record' => null,
            'fkOptions' => $this->formOptions($config),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request, string $table): RedirectResponse
    {
        $this->exigirEscritura($table);
        $config = $this->tableConfig($table);

        [$rules, $labels] = $this->buildRules($config, isEdit: false, table: $table);

        Validator::make($request->all(), $rules, [], $labels)->validate();

        $data = $this->extractData($request, $config, isEdit: false);

        // Todo lo que se crea queda a nombre de la óptica del administrador
        // (si no, la base de datos lo asignaría a la óptica principal).
        if (Alcance::tieneNit($table)) {
            $data['nit_empresa'] = Alcance::nit();
        }

        if ($error = $this->reglasDelNegocio($table, $data)) {
            return back()->withInput()->withErrors($error);
        }

        try {
            DB::table($table)->insert($data);
        } catch (QueryException $e) {
            return back()->withInput()->withErrors([
                'general' => $this->friendlyDbError($e),
            ]);
        }

        return redirect()
            ->route('admin.crud.index', $table)
            ->with('success', ucfirst($config['label']).' creado correctamente.');
    }

    public function edit(string $table, string $id): View
    {
        $this->exigirEscritura($table);
        $config = $this->tableConfig($table);
        $pk = $config['primary_key'];

        $record = Alcance::restringir(DB::table($table), $table)->where("{$table}.{$pk}", $id)->first();
        if (! $record) {
            throw new NotFoundHttpException('Registro no encontrado.');
        }

        return view('admin.crud.form', [
            'table' => $table,
            'config' => $config,
            'record' => $record,
            'fkOptions' => $this->formOptions($config),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, string $table, string $id): RedirectResponse
    {
        $this->exigirEscritura($table);
        $config = $this->tableConfig($table);
        $pk = $config['primary_key'];

        $exists = Alcance::restringir(DB::table($table), $table)->where("{$table}.{$pk}", $id)->exists();
        if (! $exists) {
            throw new NotFoundHttpException('Registro no encontrado.');
        }

        [$rules, $labels] = $this->buildRules($config, isEdit: true, table: $table, currentId: $id);

        Validator::make($request->all(), $rules, [], $labels)->validate();

        $data = $this->extractData($request, $config, isEdit: true);

        if ($error = $this->reglasDelNegocio($table, $data, $id)) {
            return back()->withInput()->withErrors($error);
        }

        try {
            DB::transaction(function () use ($table, $config, $pk, $id, $data) {
                $newId = $data[$pk] ?? null;

                if ($newId !== null && (string) $newId !== (string) $id) {
                    // Cambió la llave primaria (ej. documento del optómetra):
                    // se actualiza también en todas las tablas que la usan.
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    try {
                        foreach ($this->referencingColumns($table, $pk) as [$refTable, $refColumn]) {
                            DB::table($refTable)->where($refColumn, $id)->update([$refColumn => $newId]);
                        }
                        DB::table($table)->where($pk, $id)->update($data);
                    } finally {
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    }
                } else {
                    unset($data[$pk]);
                    if (! empty($data)) {
                        DB::table($table)->where($pk, $id)->update($data);
                    }
                }
            });
        } catch (QueryException $e) {
            return back()->withInput()->withErrors([
                'general' => $this->friendlyDbError($e),
            ]);
        }

        return redirect()
            ->route('admin.crud.index', $table)
            ->with('success', ucfirst($config['label']).' actualizado correctamente.');
    }

    /**
     * Validaciones propias del negocio que no dependen de una sola columna.
     * Devuelve [campo => mensaje] si hay un problema, o null si todo está bien.
     */
    protected function reglasDelNegocio(string $table, array $data, ?string $id = null): ?array
    {
        // Las llaves foráneas solo pueden apuntar a registros de la propia óptica.
        foreach ($this->tableConfig($table)['columns'] as $col) {
            $valor = $data[$col['name']] ?? null;
            if (empty($col['is_fk']) || $valor === null || $valor === '') {
                continue;
            }

            if ($col['fk_table'] === 'rol' && (int) $valor === (int) config('visioptica.rol_superadmin')) {
                return [$col['name'] => 'Ese rol no está disponible.'];
            }

            if (Alcance::aplica($col['fk_table'])
                && ! Alcance::restringir(DB::table($col['fk_table']), $col['fk_table'])
                    ->where("{$col['fk_table']}.{$col['fk_column']}", $valor)->exists()) {
                return [$col['name'] => 'El valor elegido no pertenece a tu óptica.'];
            }
        }

        if ($table === 'asignacion_cita' && ! empty($data['fecha_cita']) && ! empty($data['hora_cita']) && ! empty($data['id_optometra'])) {
            $cancelado = \App\Support\AgendaCitas::estadoCancelado();
            $esCancelada = $cancelado && (int) ($data['id_estado'] ?? 0) === (int) $cancelado;

            if (! $esCancelada && \App\Support\AgendaCitas::citaOcupada(
                substr($data['fecha_cita'], 0, 10), $data['hora_cita'], (int) $data['id_optometra'], $id !== null ? (int) $id : null
            )) {
                return ['hora_cita' => 'Ese optómetra ya tiene una cita ese día a esa hora. Escoge otra hora u otro optómetra.'];
            }
        }

        return null;
    }

    /** Tablas y columnas (según la configuración) que apuntan a table.column. */
    protected function referencingColumns(string $table, string $column): array
    {
        $refs = [];
        foreach (config('admin_tables.tables') as $t => $cfg) {
            foreach ($cfg['columns'] as $col) {
                if (! empty($col['is_fk']) && $col['fk_table'] === $table && $col['fk_column'] === $column) {
                    $refs[] = [$t, $col['name']];
                }
            }
        }

        return $refs;
    }

    public function destroy(string $table, string $id): RedirectResponse
    {
        $this->exigirEscritura($table);
        $config = $this->tableConfig($table);
        $pk = $config['primary_key'];

        if (! Alcance::restringir(DB::table($table), $table)->where("{$table}.{$pk}", $id)->exists()) {
            throw new NotFoundHttpException('Registro no encontrado.');
        }

        try {
            DB::table($table)->where($pk, $id)->delete();
        } catch (QueryException $e) {
            return back()->withErrors([
                'general' => $this->friendlyDbError($e),
            ]);
        }

        return redirect()
            ->route('admin.crud.index', $table)
            ->with('success', ucfirst($config['label']).' eliminado correctamente.');
    }

    /**
     * Construye las reglas de validación de Laravel a partir de
     * la metadata de columnas de config/admin_tables.php.
     */
    protected function buildRules(array $config, bool $isEdit, string $table = '', ?string $currentId = null): array
    {
        $rules = [];
        $labels = [];

        foreach ($config['columns'] as $col) {
            if (! $this->inForm($col, $isEdit)) {
                continue;
            }
            if ($col['manual_pk'] && $isEdit && empty($col['pk_editable'])) {
                continue; // la clave manual no se puede cambiar al editar
            }

            $name = $col['name'];
            $labels[$name] = $col['label'];
            $fieldRules = [];

            $required = ! $col['nullable'] && $col['type'] !== 'password';
            if ($col['type'] === 'password') {
                // En edición la contraseña es opcional (se conserva si se deja vacía)
                $fieldRules[] = $isEdit ? 'nullable' : 'required';
                $fieldRules[] = 'string';
                $fieldRules[] = 'min:4';
            } elseif ($col['type'] === 'file') {
                // La imagen siempre es opcional: al editar, si no se sube una
                // nueva, se conserva la que ya había (ver extractData()).
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'file';
                $fieldRules[] = 'mimes:jpeg,jpg,png,gif,webp,avif';
                $fieldRules[] = 'max:2048';
            } else {
                $fieldRules[] = $required ? 'required' : 'nullable';
            }

            switch ($col['type']) {
                case 'number':
                    $fieldRules[] = 'integer';
                    break;
                case 'decimal':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                case 'datetime-local':
                    $fieldRules[] = 'date';
                    break;
                case 'time':
                    // Acepta 08:00 y 08:00:00 (así la devuelve la base de datos)
                    $fieldRules[] = 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';
                    break;
                case 'select':
                    if ($col['is_fk']) {
                        $fieldRules[] = Rule::exists($col['fk_table'], $col['fk_column']);
                    } elseif (! empty($col['options'])) {
                        $fieldRules[] = Rule::in($col['options']);
                    }
                    break;
            }

            if ($col['type'] === 'email' || in_array($name, ['correo', 'email'], true)) {
                $fieldRules[] = 'email';
            }

            if ($col['manual_pk'] && $table !== '') {
                $unique = Rule::unique($table, $name);
                if ($isEdit && $currentId !== null) {
                    $unique->ignore($currentId, $name);
                }
                $fieldRules[] = $unique;
            }

            // Límites según la estructura real de la base de datos
            $db = $col['db'] ?? [];
            if (isset($db['max_length']) && in_array($col['type'], ['text', 'email', 'textarea'], true)) {
                $fieldRules[] = 'max:'.$db['max_length'];
            }
            if (isset($db['max']) && in_array($col['type'], ['number', 'decimal'], true)) {
                $fieldRules[] = 'min:'.($col['min'] ?? $db['min']);
                $fieldRules[] = 'max:'.($col['max'] ?? $db['max']);
            }

            foreach ($col['rules'] ?? [] as $extra) {
                $fieldRules[] = $extra;
            }

            $rules[$name] = $fieldRules;
        }

        return [$rules, $labels];
    }

    /**
     * Prepara el array de datos a insertar/actualizar (hash de contraseñas,
     * fechas automáticas, archivos, normalización de fechas y horas, etc.).
     */
    protected function extractData(Request $request, array $config, bool $isEdit): array
    {
        $data = [];

        foreach ($config['columns'] as $col) {
            $name = $col['name'];

            if ($col['auto']) {
                continue;
            }

            // Fechas que pone el sistema: solo al crear, nunca se editan
            if (! empty($col['on_create'])) {
                if (! $isEdit) {
                    $data[$name] = $col['on_create'] === 'today'
                        ? now()->toDateString()
                        : now()->format('Y-m-d H:i:s');
                }
                continue;
            }

            if (isset($col['in_form']) && $col['in_form'] === false) {
                continue;
            }
            if ($col['manual_pk'] && $isEdit && empty($col['pk_editable'])) {
                continue;
            }

            if ($col['type'] === 'password') {
                $value = $request->input($name);
                if ($value === null || $value === '') {
                    continue; // no se toca la contraseña existente
                }
                $data[$name] = Hash::make($value);
                continue;
            }

            if ($col['type'] === 'file') {
                if (! $request->hasFile($name)) {
                    continue; // no se subió un archivo nuevo: se conserva el que ya había
                }

                $archivo = $request->file($name);
                $carpetaDestino = public_path($col['upload_dir'] ?? 'assets/img/productos');
                if (! file_exists($carpetaDestino)) {
                    mkdir($carpetaDestino, 0777, true);
                }

                // Str::slug() evita que un nombre con espacios o tildes
                // (ej. "gafas de sol.jpg") rompa la URL de la imagen.
                $nombreOriginal = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = strtolower($archivo->getClientOriginalExtension());
                $nombreArchivo = time().'_'.Str::slug($nombreOriginal).'.'.$extension;

                $archivo->move($carpetaDestino, $nombreArchivo);
                $data[$name] = $nombreArchivo;
                continue;
            }

            if (! $request->has($name)) {
                continue;
            }

            $value = $request->input($name);

            if ($value === '') {
                $value = null;
            } elseif ($col['type'] === 'datetime-local' && $value) {
                $value = str_replace('T', ' ', $value);
                if (strlen($value) === 16) {
                    $value .= ':00';
                }
            } elseif ($col['type'] === 'time' && $value && strlen($value) === 5) {
                $value .= ':00';
            }

            $data[$name] = $value;
        }

        return $data;
    }

    protected function friendlyDbError(QueryException $e): string
    {
        report($e); // queda en storage/logs/laravel.log para revisar el detalle

        $msg = $e->getMessage();

        if (str_contains($msg, 'a foreign key constraint fails')) {
            if (stripos($msg, 'cannot delete or update a parent row') !== false) {
                return 'No se puede eliminar: hay otros registros que dependen de este dato.';
            }

            return 'El valor seleccionado no existe en la tabla relacionada.';
        }

        if (str_contains($msg, 'Duplicate entry')) {
            return 'Ya existe un registro con ese valor único (por ejemplo, correo o documento repetido).';
        }

        if (str_contains($msg, 'Out of range value')) {
            return 'Uno de los números es demasiado grande para guardarse en ese campo.';
        }

        if (str_contains($msg, "doesn't have a default value") || str_contains($msg, 'cannot be null')) {
            return 'Falta un dato obligatorio para guardar este registro.';
        }

        return 'Ocurrió un error al guardar en la base de datos.';
    }
}
