<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Separa los datos de cada óptica en el panel de administración.
 *
 * Cada administrador solo debe ver y tocar lo de SU óptica (usuario.nit_empresa).
 *  - Las tablas que tienen la columna `nit_empresa` se filtran directamente.
 *  - Las que no la tienen (fórmulas, historias, detalles de venta...) se filtran
 *    a través de la tabla "padre" que sí pertenece a una óptica (ver VIA).
 *  - OCULTAS: tablas del sistema que un administrador de óptica no debe ver (roles).
 *  - SOLO_LECTURA: catálogos compartidos por todas las ópticas; se pueden consultar
 *    pero no modificar, porque un cambio afectaría a todas.
 */
class Alcance
{
    public const OCULTAS = ['rol'];

    public const SOLO_LECTURA = ['departamento', 'ciudad', 'tipo_documento', 'estado'];

    /**
     * tabla => lista de [columna_propia, tabla_padre, columna_del_padre].
     * Basta con que el registro apunte a un padre de la óptica (cualquiera de las rutas).
     */
    protected const VIA = [
        'formula_medica' => [['documento_paciente', 'usuario', 'documento']],
        'carrito' => [['id_usuario', 'usuario', 'documento']],
        'compra_online' => [['id_usuario', 'usuario', 'documento'], ['id_usuario_nr', 'usuarios_no_registrados', 'id_usuario_nr']],
        'historia_clinica' => [['id_asignacioncita', 'asignacion_cita', 'id_cita']],
        'diagnostico' => [['id_formula', 'formula_medica', 'id_formula']],
        'detalle_formula_medica_medicamento' => [['id_formula', 'formula_medica', 'id_formula']],
        'detalle_formula_medica_procedimiento' => [['id_formula', 'formula_medica', 'id_formula']],
        'agendamiento_procedimiento' => [['id_formula', 'formula_medica', 'id_formula']],
        'evolucion_terapia' => [['id_agendamiento_procedimiento', 'agendamiento_procedimiento', 'id_agendamiento_procedimiento']],
        'detalle_pedido_producto' => [['id_pedido', 'pedido', 'id_pedido']],
        'detalle_productos' => [['id_venta', 'venta', 'id_venta']],
        'detalle_venta_producto' => [['id_venta', 'venta', 'id_venta']],
        'detalle_venta_medicamento' => [['id_venta_med', 'venta_medicamento', 'id_venta_med']],
        'detalle_carrito_producto' => [['id_carrito', 'carrito', 'id_carrito']],
        'envio' => [['id_venta', 'venta', 'id_venta']],
        'seguimiento_envio' => [['id_envio', 'envio', 'id_envio']],
    ];

    /** NIT de la óptica del administrador que tiene la sesión abierta. */
    public static function nit(): ?string
    {
        return Auth::user()?->nit_empresa ?: null;
    }

    public static function oculta(string $table): bool
    {
        return in_array($table, self::OCULTAS, true);
    }

    public static function soloLectura(string $table): bool
    {
        return in_array($table, self::SOLO_LECTURA, true);
    }

    /** ¿La tabla tiene columna nit_empresa? */
    public static function tieneNit(string $table): bool
    {
        static $cache = [];

        return $cache[$table] ??= Schema::hasColumn($table, 'nit_empresa');
    }

    /** ¿Los datos de esta tabla pertenecen a una óptica (y por tanto se filtran)? */
    public static function aplica(string $table): bool
    {
        return self::tieneNit($table) || isset(self::VIA[$table]);
    }

    /**
     * Limita la consulta a lo que pertenece a la óptica. La tabla principal de la
     * consulta debe ser $table (sin alias). Si la tabla es compartida, no cambia nada.
     */
    public static function restringir(Builder $query, string $table, ?string $nit = null): Builder
    {
        if (! self::aplica($table)) {
            return $query;
        }

        $nit ??= self::nit();
        if (! $nit) {
            return $query->whereRaw('1 = 0'); // sin óptica asignada no se ve nada
        }

        if (self::tieneNit($table)) {
            return $query->where("{$table}.nit_empresa", $nit);
        }

        return $query->where(function ($grupo) use ($table, $nit) {
            foreach (self::VIA[$table] as [$columna, $padre, $clavePadre]) {
                $grupo->orWhereIn("{$table}.{$columna}", function ($sub) use ($padre, $clavePadre, $nit) {
                    $sub->select("{$padre}.{$clavePadre}")->from($padre);
                    self::restringir($sub, $padre, $nit);
                });
            }
        });
    }
}
