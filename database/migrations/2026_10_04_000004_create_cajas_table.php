<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de caja: apertura con fondo inicial, movimientos de efectivo y cierre
 * con arqueo.
 *
 * Decisión de diseño: hay una caja abierta por persona y momento. Así cada turno
 * responde por su propio dinero y dos cajeros pueden trabajar el mismo día sin
 * compartir la misma caja; si hubiera una sola caja para todo el día, el monto
 * inicial del segundo turno se mezclaría con el del primero y el arqueo dejaría
 * de tener sentido.
 *
 * El índice de (user_id, fecha) no es único a propósito, para que un mismo cajero
 * pueda abrir una segunda caja el mismo día si el turno se parte en dos. Lo que no
 * se permite es tener dos cajas abiertas a la vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();

            // Quién abrió la caja. No es un "tipo de usuario" fijo: puede ser el
            // administrador o un cajero.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Día de operación de la caja, no la fecha en que se creó el registro.
            // Si alguien abre la caja pasada la medianoche sigue siendo la caja de
            // ayer, que es como se cuenta en un restaurante real.
            $table->date('fecha');

            // Con cuánto dinero arrancó el turno.
            $table->decimal('monto_inicial', 10, 2)->default(0);

            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');

            $table->timestamp('apertura_at')->nullable();
            $table->timestamp('cierre_at')->nullable();

            // --- Datos del arqueo, todos nulos mientras la caja está abierta ---
            // Se rellenan al cerrar y NO se vuelven a calcular nunca: son la
            // foto de cómo estaba el dinero en ese momento. Si se recalcularan
            // más tarde, un cobro de un día anterior podría alterar el cierre de
            // un día ya cerrado.
            //
            // Se guardan por separado las ventas de salón y los cobros de delivery
            // porque son dos cosas distintas: unas nacen cerradas en el mostrador y
            // las otras se cobran después. Sumarlas en un único número haría
            // imposible saber de dónde salió el dinero.
            $table->decimal('total_ventas', 10, 2)->nullable();
            $table->decimal('total_cobros', 10, 2)->nullable();
            $table->decimal('total_entradas', 10, 2)->nullable();
            $table->decimal('total_salidas', 10, 2)->nullable();
            // Lo que la caja debería tener según los números del sistema.
            $table->decimal('total_esperado', 10, 2)->nullable();
            // Lo que el cajero contó a mano.
            $table->decimal('total_declarado', 10, 2)->nullable();
            // declarado - esperado. Posiva: sobró dinero. Negativa: faltó.
            $table->decimal('diferencia', 10, 2)->nullable();

            $table->text('observacion_apertura')->nullable();
            $table->text('observacion_cierre')->nullable();

            $table->timestamps();

            // Índice de apoyo para buscar "la caja de esta persona hoy".
            //
            // NO es único a propósito: si lo fuera, un cajero no podría abrir una
            // segunda caja el mismo día, y eso es legítimo cuando el turno se
            // parte en dos o cuando una caja se cierra antes de tiempo. Lo que no
            // puede existir son dos cajas ABIERTAS a la vez de la misma persona, y
            // eso lo comprueba el formulario de apertura (CajaAperturaRequest),
            // que da un mensaje entendible en vez de una excepción de SQL.
            $table->index(['user_id', 'fecha']);
            $table->index(['user_id', 'estado']);
        });

        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();

            // 'entrada' = dinero que entra a la caja (retiro de un cajero al que se
            // le olvidó entregar algo, cambio de un cliente).
            // 'salida' = dinero que sale (retiro a la bóveda, pago del gastos del día).
            $table->enum('tipo', ['entrada', 'salida']);

            $table->decimal('monto', 10, 2);
            $table->string('concepto', 150);

            // Quién lo registró. Puede no ser el dueño de la caja: el administrador
            // puede revisar y registrar movimientos en la caja de un cajero.
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