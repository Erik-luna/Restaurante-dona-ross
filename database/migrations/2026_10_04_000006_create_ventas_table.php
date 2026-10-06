<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La venta de salón como entidad propia.
 *
 * Antes esta operación se guardaba en la tabla `pedidos` con `tipo = 'local'`, y
 * `pedidos` guardaba a la vez las dos cosas del sistema: el delivery que viene de
 * la web y la venta que ocurre en el mostrador. Son la misma operación contable
 * pero no el mismo negocio: un pedido de delivery lo prepara la cocina y lo cobra
 * el cajero después, mientras que una venta de salón se cobra en el momento.
 *
 * Con una sola tabla había que acordarse siempre de añadir `where('tipo', ...)` a
 * cada consulta; olvidarlo devolvía datos de las dos cosas mezclados. Con dos
 * entidades, cada consulta dice sola qué está pidiendo.
 *
 * Por eso `pedidos` pasa a ser sólo delivery y `ventas` sólo salón.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            // El cliente puede ir vacío: en el salón se consume en mesa sin dejar
            // datos. La venta sigue siendo válida.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Caja donde entró el dinero.
            //
            // Nulo sólo para las ventas que ya existían antes de este módulo: no
            // eran atribuibles a ninguna caja porque no las había. Las ventas nuevas
            // siempre lo llevan (lo garantiza el controlador, que rechaza la venta
            // si no hay caja abierta).
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();

            // Una venta de salón se cobra al instante, así que nace 'pagada'.
            // 'anulada' es para una devolución o un error de registro: el dinero se
            // devolvió, así que no debe contar en el arqueo.
            $table->enum('estado', ['pagada', 'anulada'])->default('pagada');

            $table->decimal('total', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'qr', 'tarjeta'])->default('efectivo');
            $table->decimal('monto_recibido', 10, 2)->nullable();
            $table->decimal('cambio', 10, 2)->nullable();

            $table->text('notas')->nullable();

            // Quién atendió la mesa.
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // El arqueo y los listados filtran por caja, y los listados de sala
            // ordenan por fecha.
            $table->index('caja_id');
            $table->index('user_id');
        });

        Schema::create('venta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->integer('cantidad');

            // Se guarda el precio con el que se vendió, no el precio actual del
            // producto: si mañana suben los precios, el histórico no debe cambiar.
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