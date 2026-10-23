<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_jornadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('inicial')->default(0);
            $table->unsignedInteger('entradas')->default(0);
            $table->unsignedInteger('mermas')->default(0);
            $table->unsignedInteger('consumidos')->default(0);
            $table->string('motivo', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['producto_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_jornadas');
    }
};