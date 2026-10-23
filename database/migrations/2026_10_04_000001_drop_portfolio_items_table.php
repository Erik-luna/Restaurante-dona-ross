<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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