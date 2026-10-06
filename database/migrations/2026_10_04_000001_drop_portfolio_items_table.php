<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina el módulo de portafolio personal.
     *
     * Este módulo no pertenece al sistema de ventas del Restaurante Doña Ross:
     * almacenaba habilidades, experiencia y proyectos del desarrollador. Se creó en
     * una instalación anterior, así que esta migración borra la tabla en las bases
     * de datos que ya la tienen. Las instalaciones nuevas nunca la crean, porque la
     * migración que la definía fue eliminada.
     *
     * Los datos que se borran son únicamente de prueba: no afectan pedidos, ventas
     * ni caja.
     */
    public function up(): void
    {
        Schema::dropIfExists('portfolio_items');
    }

    public function down(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['proyecto', 'habilidad', 'experiencia', 'educacion', 'sobre_mi'])->default('proyecto');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('tecnologias')->nullable();
            $table->string('enlace')->nullable();
            $table->string('imagen')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }
};