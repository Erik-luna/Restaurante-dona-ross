<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('monto_inicial', 10, 2)->default(0);
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->timestamp('apertura_at')->nullable();
            $table->timestamp('cierre_at')->nullable();
            $table->decimal('total_ventas', 10, 2)->nullable();
            $table->decimal('total_cobros', 10, 2)->nullable();
            $table->decimal('total_entradas', 10, 2)->nullable();
            $table->decimal('total_salidas', 10, 2)->nullable();
            $table->decimal('total_esperado', 10, 2)->nullable();
            $table->decimal('total_declarado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();

            $table->text('observacion_apertura')->nullable();
            $table->text('observacion_cierre')->nullable();

            $table->timestamps();
            $table->index(['user_id', 'fecha']);
            $table->index(['user_id', 'estado']);
        });

        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->decimal('monto', 10, 2);
            $table->string('concepto', 150);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['caja_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
        Schema::dropIfExists('cajas');
    }
};