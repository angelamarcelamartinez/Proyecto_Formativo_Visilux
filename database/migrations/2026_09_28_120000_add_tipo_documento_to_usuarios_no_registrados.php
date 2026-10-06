<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el tipo de documento a los usuarios no registrados
 * (quienes solicitan cita sin tener cuenta).
 *
 * Es opcional (NULL) para no afectar las solicitudes que ya existen.
 * También cambia el teléfono del optómetra a texto (VARCHAR).
 */
return new class extends Migration
{
    public function up(): void
    {
        // El teléfono del optómetra era INT(11) y un celular de 10 dígitos no cabía
        DB::statement('ALTER TABLE `optometra` MODIFY `telefono` VARCHAR(20) NOT NULL');

        if (Schema::hasColumn('usuarios_no_registrados', 'id_tipo_docu')) {
            return;
        }

        Schema::table('usuarios_no_registrados', function (Blueprint $table) {
            $table->integer('id_tipo_docu')->nullable()->after('id_usuario_nr');
            $table->foreign('id_tipo_docu', 'fk_usuario_nr_tipo_docu')
                ->references('id_tipo_docu')->on('tipo_documento');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('usuarios_no_registrados', 'id_tipo_docu')) {
            return;
        }

        Schema::table('usuarios_no_registrados', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_nr_tipo_docu');
            $table->dropColumn('id_tipo_docu');
        });
    }
};
