<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->enum('estado', ['pagada', 'anulada'])->default('pagada');
            $table->decimal('total', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'qr', 'tarjeta'])->default('efectivo');
            $table->decimal('monto_recibido', 10, 2)->nullable();
            $table->decimal('cambio', 10, 2)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('caja_id');
            $table->index('user_id');
        });

        Schema::create('venta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_items');
        Schema::dropIfExists('ventas');
    }
};