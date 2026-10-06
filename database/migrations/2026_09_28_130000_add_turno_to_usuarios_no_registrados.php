<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las solicitudes de cita de visitantes guardan el turno escogido
 * (fecha, hora, optómetra) y un estado, para que el turno quede
 * apartado mientras la solicitud esté "Pendiente".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('usuarios_no_registrados', 'fecha_cita')) {
            return;
        }

        Schema::table('usuarios_no_registrados', function (Blueprint $table) {
            $table->date('fecha_cita')->nullable()->after('motivo_cita');
            $table->time('hora_cita')->nullable()->after('fecha_cita');
            $table->integer('id_optometra')->nullable()->after('hora_cita');
            $table->integer('id_estado')->nullable()->after('id_optometra');

            $table->foreign('id_optometra', 'fk_usuario_nr_optometra')
                ->references('doc_optometra')->on('optometra')->cascadeOnUpdate();
            $table->foreign('id_estado', 'fk_usuario_nr_estado')
                ->references('id_estado')->on('estado');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('usuarios_no_registrados', 'fecha_cita')) {
            return;
        }

        Schema::table('usuarios_no_registrados', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_nr_optometra');
            $table->dropForeign('fk_usuario_nr_estado');
            $table->dropColumn(['fecha_cita', 'hora_cita', 'id_optometra', 'id_estado']);
        });
    }
};
