<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
/**
 * Tabla de promociones del restaurante: descuentos por tiempo y porcentaje.
 *
 * Nota para quien lea esto: esta migración se llama ahora
 * `create_promociones_table`, pero en las bases ya instaladas seguía registrada
 * con el nombre anterior, `create_portfolio_items_table`, porque antes creaba dos
 * tablas en una sola migración. Al renombrar el archivo se actualizó también esa
 * fila de la tabla `migrations`, de modo que no vuelva a ejecutarse sobre
 * instalaciones que ya la tenían aplicada.
 */
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->decimal('descuento_porcentaje', 5, 2)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};