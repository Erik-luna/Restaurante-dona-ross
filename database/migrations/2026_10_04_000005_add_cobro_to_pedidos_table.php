<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos de cobro en los pedidos.
 *
 * Una venta de salón y un pedido de delivery son la misma operación contable, así
 * que el cobro se registra en la misma tabla: no hay una tabla "ventas" aparte.
 * El dinero de un pedido entra en la caja que lo cobró (caja_id), y no el que lo
 * preparó.
 *
 * `pagado_at` es lo que separa "cobrado" de "por cobrar": un pedido de delivery
 * entregado pero sin pagado_at sigue debiendo dinero, y por eso aparece en el
 * apartado "por cobrar" del panel del cajero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Caja que cobró este pedido. Nulo si aún no se ha cobrado.
            $table->foreignId('caja_id')->nullable()->after('atendido_por')
                ->constrained('cajas')->nullOnDelete();

            $table->enum('metodo_pago', ['efectivo', 'qr', 'tarjeta'])->nullable();

            // Lo que entregó el cliente. Sólo tiene sentido con efectivo; con QR o
            // tarjeta el dinero entra completo y no hay cambio que dar.
            $table->decimal('monto_recibido', 10, 2)->nullable();

            // El cambio lo calcula el servidor como monto_recibido - total. Nunca
            // se recibe del formulario: si el cliente lo enviara, podría pedir que
            // el sistema le devuelva más dinero del que entregó.
            $table->decimal('cambio', 10, 2)->nullable();

            $table->timestamp('pagado_at')->nullable();
        });

        // Búsquedas frecuentes: los pedidos que aún no se han cobrado y los que
        // pertenecen a una caja concreta (para el arqueo).
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