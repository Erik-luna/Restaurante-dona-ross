<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El stock como una jornada, no como un número suelto en el producto.
 *
 * Antes `productos.stock` era un único entero que iba bajando conforme se vendía,
 * sin fecha ni rastro. Eso obliga a elegir entre dos cosas que no pueden convivir:
 *  - o el número es "lo que queda hoy", y entonces cada mañana hay que acordarlo a
 *    mano porque nadie sabe cuánto se había preparado;
 *  - o el número es "el inventario total", y entonces no sirve para saber si un
 *    plato está disponible hoy, que es lo que necesita el cliente del catálogo.
 *
 * Con una fila por producto y día el sistema puede responder las dos preguntas:
 * cuántas porciones hay disponibles ahora, y cómo se llegó hasta ahí.
 *
 * Las cuatro columnas son datos que ALGUIEN decidió (o que el sistema consume de
 * forma automática); ninguna es un campo calculado. La cantidad disponible no se
 * guarda: se deriva de estas cuatro, para que no pueda quedar desincronizada con
 * los movimientos que la producen.
 *
 *   disponible = inicial + entradas - mermas - consumidos
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_jornadas', function (Blueprint $table) {
            $table->id();

            // Si se borra un producto sus jornadas se van con él: sus ventas ya no
            // se pueden mostrar, pero el producto nunca se borra si tuvo historial.
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();

            $table->date('fecha');

            // Con cuántas porciones arrancó el día. Lo decide quien abre el servicio.
            $table->unsignedInteger('inicial')->default(0);

            // Reposiciones durante el día: lo que llega del proveedor o de la cocina.
            $table->unsignedInteger('entradas')->default(0);

            // Porciones que se perdieron: se cayó, se quemó, se regalaron. Cuentan
            // como salida aunque no se hayan cobrado, y por eso van separadas de
            // 'consumidos': un plato que se estropeó no es una venta.
            $table->unsignedInteger('mermas')->default(0);

            // Porciones que se vendieron. Lo incrementan los pedidos de delivery y
            // las ventas de salón en la MISMA transacción en que se guarda su línea,
            // de modo que no puede haber una venta sin su descuento de stock.
            $table->unsignedInteger('consumidos')->default(0);

            // Por qué se hizo el último ajuste manual. Sin esto, un cambio de
            // existencias sería un número que aparece y nadie sabe su origen.
            $table->string('motivo', 255)->nullable();

            // Quién hizo ese último ajuste.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Un producto no puede tener dos jornadas el mismo día: la segunda fila
            // sería una fuente de verdad más y el disponible quedaría ambiguo.
            $table->unique(['producto_id', 'fecha']);

            // Las consultas de siempre son "la jornada de hoy": sin este índice
            // tendría que recorrer todas las jornadas de todos los productos.
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_jornadas');
    }
};