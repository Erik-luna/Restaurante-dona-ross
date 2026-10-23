<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('caja_id')->nullable()->after('atendido_por')
                ->constrained('cajas')->nullOnDelete();
            $table->enum('metodo_pago', ['efectivo', 'qr', 'tarjeta'])->nullable();
            $table->decimal('monto_recibido', 10, 2)->nullable();
            $table->decimal('cambio', 10, 2)->nullable();

            $table->timestamp('pagado_at')->nullable();
        });
        Schema::table('pedidos', function (Blueprint $table) {
            $table->index('pagado_at');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['caja_id']);
            $table->dropColumn(['caja_id', 'metodo_pago', 'monto_recibido', 'cambio', 'pagado_at']);
        });
    }
};