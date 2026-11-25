<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes finales según la plantilla de auditoría (25-11-2026).
 * Es lo mismo que database/sql/2026_11_25_auditoria_final.sql, pero versionado y reversible:
 *   php artisan migrate            -> aplica (up)
 *   php artisan migrate:rollback   -> deshace (down)
 *
 * Si la base se importó con visilux_corregido.sql, esta migración ya aparece como ejecutada
 * en la tabla migrations y Laravel no la vuelve a correr.
 */
return new class extends Migration
{
    /** Nombres cortos que pasan de TEXT a VARCHAR: tabla => [columna => definición]. */
    private const NOMBRES = [
        'usuario' => ['nombres' => 'VARCHAR(100) NOT NULL', 'apellido' => 'VARCHAR(100) NOT NULL'],
        'optometra' => ['nombre' => 'VARCHAR(100) NOT NULL', 'apellido' => 'VARCHAR(100) NOT NULL'],
        'producto' => ['nombre_producto' => 'VARCHAR(150) NOT NULL', 'color' => 'VARCHAR(50) NULL'],
        'categoria_producto' => ['nombre_categoria' => 'VARCHAR(100) NOT NULL'],
        'ciudad' => ['nombre_ciudad' => 'VARCHAR(100) NOT NULL'],
        'departamento' => ['nom_departamento' => 'VARCHAR(100) NOT NULL'],
        'enfermedades' => ['nombre_enfer' => 'VARCHAR(150) NOT NULL'],
        'estado' => ['nom_estado' => 'VARCHAR(50) NOT NULL'],
        'marca' => ['nom_marca' => 'VARCHAR(100) NOT NULL'],
        'modelo' => ['nom_modelo' => 'VARCHAR(100) NOT NULL'],
        'rol' => ['nombre_rol' => 'VARCHAR(50) NOT NULL'],
        'tipo_cita' => ['nombre_tipo' => 'VARCHAR(100) NOT NULL'],
        'tipo_documento' => ['nom_ti_docu' => 'VARCHAR(50) NOT NULL'],
        'tipo_producto' => ['nomb_tipo' => 'VARCHAR(100) NOT NULL'],
        'tipo_terapia' => ['nomtipo_terp' => 'VARCHAR(100) NOT NULL'],
        'envio' => ['ciudad' => 'VARCHAR(100) NULL', 'direccion_envio' => 'VARCHAR(255) NULL'],
    ];

    /** Tablas que reciben created_at / updated_at (usuario y optometra ya tienen fecha_creacion). */
    private const CON_FECHAS = ['asignacion_cita', 'producto', 'lote', 'venta', 'pedido'];

    /** Índices repetidos (ya los cubre un índice compuesto): [tabla, índice que sobra, su columna]. */
    private const INDICES = [
        ['asignacion_cita', 'fk_asignacion_cita_empresa', 'nit_empresa'],
        ['asignacion_cita', 'id_optometra', 'id_optometra'],
        ['formula_medica', 'fk_formula_usuario', 'documento_paciente'],
        ['historia_clinica', 'id_asignacioncita', 'id_asignacioncita'],
        ['lote', 'fk_lote_producto', 'id_producto'],
        ['venta', 'fk_venta_empresa', 'nit_empresa'],
        ['venta', 'id_usuario', 'id_usuario'],
    ];

    public function up(): void
    {
        // 1. Normalización (3FN): la especialidad se lee de la tabla especialidad.
        DB::statement('ALTER TABLE optometra DROP COLUMN IF EXISTS especialidad');

        // 2. Estructura: nombres en VARCHAR y columnas de auditoría.
        foreach (self::NOMBRES as $tabla => $columnas) {
            foreach ($columnas as $columna => $definicion) {
                DB::statement("ALTER TABLE {$tabla} MODIFY {$columna} {$definicion}");
            }
        }
        foreach (['usuario', 'optometra'] as $tabla) {
            DB::statement("ALTER TABLE {$tabla} ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        }
        foreach (self::CON_FECHAS as $tabla) {
            DB::statement("ALTER TABLE {$tabla}
                ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        }

        // 3. Relaciones de simulacion_visual.
        DB::statement('DELETE FROM simulacion_visual WHERE id_optometra NOT IN (SELECT doc_optometra FROM optometra)');
        DB::statement('UPDATE simulacion_visual SET documento_paciente = NULL
            WHERE documento_paciente IS NOT NULL AND documento_paciente NOT IN (SELECT documento FROM usuario)');
        DB::statement('ALTER TABLE simulacion_visual
            ADD CONSTRAINT fk_sim_optometra FOREIGN KEY IF NOT EXISTS (id_optometra) REFERENCES optometra (doc_optometra) ON UPDATE CASCADE,
            ADD CONSTRAINT fk_sim_paciente FOREIGN KEY IF NOT EXISTS (documento_paciente) REFERENCES usuario (documento) ON DELETE SET NULL ON UPDATE CASCADE');

        // 5. Índices repetidos.
        foreach (self::INDICES as [$tabla, $indice]) {
            DB::statement("ALTER TABLE {$tabla} DROP INDEX IF EXISTS {$indice}");
        }

        // 6. producto.id_producto autoincremental.
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE producto MODIFY id_producto INT(11) NOT NULL AUTO_INCREMENT');
        Schema::enableForeignKeyConstraints();

        // 8. Datos temporales.
        DB::table('sessions')->delete();
        DB::table('cache')->delete();
        DB::table('cache_locks')->delete();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE producto MODIFY id_producto INT(11) NOT NULL');
        Schema::enableForeignKeyConstraints();

        foreach (self::INDICES as [$tabla, $indice, $columna]) {
            DB::statement("ALTER TABLE {$tabla} ADD INDEX IF NOT EXISTS {$indice} ({$columna})");
        }

        DB::statement('ALTER TABLE simulacion_visual DROP FOREIGN KEY IF EXISTS fk_sim_optometra');
        DB::statement('ALTER TABLE simulacion_visual DROP FOREIGN KEY IF EXISTS fk_sim_paciente');

        foreach (self::CON_FECHAS as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at");
        }
        foreach (['usuario', 'optometra'] as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP COLUMN IF EXISTS updated_at");
        }
        foreach (self::NOMBRES as $tabla => $columnas) {
            foreach ($columnas as $columna => $definicion) {
                $nulo = str_contains($definicion, 'NOT NULL') ? 'NOT NULL' : 'NULL';
                DB::statement("ALTER TABLE {$tabla} MODIFY {$columna} TEXT {$nulo}");
            }
        }

        DB::statement('ALTER TABLE optometra ADD COLUMN IF NOT EXISTS especialidad TEXT NULL AFTER tarjeta_profesional');
        DB::statement('UPDATE optometra o JOIN especialidad e ON e.id_especialidad = o.id_especialidad
            SET o.especialidad = e.nombre_especialidad');
    }
};
