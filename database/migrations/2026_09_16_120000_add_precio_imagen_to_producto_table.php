<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tabla "producto" (creada fuera de Laravel, directo en MySQL)
     * nunca tuvo columnas de precio ni de imagen -- esos datos solo
     * vivían en las tablas de venta y de carrito (detalle_productos,
     * detalle_carrito_producto), nunca en el catálogo en sí. Sin un
     * precio "de catálogo", no hay forma de mostrarlo en una vitrina
     * pública antes de que el producto se venda. Esta migración agrega
     * esas 2 columnas que faltaban.
     */
    public function up(): void
    {
        Schema::table('producto', function (Blueprint $table) {
            $table->decimal('precio', 10, 2)->nullable()->after('descripcion');
            $table->string('imagen')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('producto', function (Blueprint $table) {
            $table->dropColumn(['precio', 'imagen']);
        });
    }
};
