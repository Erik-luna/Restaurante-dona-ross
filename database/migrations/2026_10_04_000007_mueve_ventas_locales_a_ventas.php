<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte las ventas de salón que estaban dentro de `pedidos` en filas de la
 * nueva tabla `ventas`, y deja `pedidos` únicamente para los pedidos de delivery.
 *
 * Se ejecuta en dos tiempos a propósito:
 *
 *  1. Copiar. Cada pedido con tipo 'local' se reproduce en `ventas` y sus líneas
 *     en `venta_items`, copiando el id original para poder saber de dónde vino.
 *  2. Borrar y quitar la columna.
 *
 * ¿Por qué copiar y no renombrar la tabla? Porque `pedidos` no se puede renombrar:
 * después de esta migración sigue siendo la tabla de los pedidos de delivery, con
 * sus propias filas. Las ventas de salón se copian a su tabla nueva y los pedidos
 * antiguos se borran. Los ids de `ventas` no tienen por qué coincidir con los que
 * tenían los pedidos, así que las referencias antiguas (notas de venta impresas,
 * comprobantes) dejan de coincidir con el número de la venta; los importes, las
 * fechas y las líneas de cada venta sí se conservan intactos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Copiar cada pedido de salón a la tabla ventas.
        $locales = DB::table('pedidos')->where('tipo', 'local')->orderBy('id')->get();

        foreach ($locales as $pedido) {
            $ventaId = DB::table('ventas')->insertGetId([
                // La caja viene nula en las ventas antiguas: no existían las cajas
                // cuando se registraron. Para las que sí la tienen, se conserva.
                'user_id' => $pedido->user_id,
                'caja_id' => $pedido->caja_id,
                // Todo pedido de salón se cobraba en el mostrador al entregarlo.
                'estado' => $pedido->estado === 'cancelado' ? 'anulada' : 'pagada',
                'total' => $pedido->total,
                'metodo_pago' => $pedido->metodo_pago ?? 'efectivo',
                'monto_recibido' => $pedido->monto_recibido,
                'cambio' => $pedido->cambio,
                'notas' => $pedido->notas,
                'atendido_por' => $pedido->atendido_por,
                // Se conservan las fechas originales: el histórico no se reescribe.
                'created_at' => $pedido->created_at,
                'updated_at' => $pedido->updated_at,
            ]);

            $lineas = DB::table('pedido_items')->where('pedido_id', $pedido->id)->orderBy('id')->get();

            foreach ($lineas as $linea) {
                DB::table('venta_items')->insert([
                    'venta_id' => $ventaId,
                    'producto_id' => $linea->producto_id,
                    'cantidad' => $linea->cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                    'subtotal' => $linea->subtotal,
                    'created_at' => $linea->created_at,
                    'updated_at' => $linea->updated_at,
                ]);
            }
        }

        // 2. Ya están copiadas: se pueden borrar los pedidos de salón y sus líneas.
        $idsLocales = $locales->pluck('id')->all();

        if ($idsLocales !== []) {
            DB::table('pedido_items')->whereIn('pedido_id', $idsLocales)->delete();
            DB::table('pedidos')->whereIn('id', $idsLocales)->delete();
        }

        // 3. `pedidos` ya no guarda ventas: la columna `tipo` dejó de tener un
        //    segundo valor posible y sobraba. Se quita para que nadie escriba
        //    where('tipo', 'local') esperando encontrar ventas donde ya no están.
        Schema::table('pedidos', function ($table) {
            $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
        // Revertir implica reconstruir los pedidos de salón a partir de las ventas.
        // Es el camino inverso: se recupera la columna, se reinsertan los pedidos y
        // sus líneas, y luego se copia cada venta de vuelta.
        Schema::table('pedidos', function ($table) {
            $table->enum('tipo', ['online', 'local'])->default('online');
        });

        foreach (DB::table('ventas')->orderBy('id')->get() as $venta) {
            $pedidoId = DB::table('pedidos')->insertGetId([
                'user_id' => $venta->user_id,
                'tipo' => 'local',
                'estado' => $venta->estado === 'anulada' ? 'cancelado' : 'entregado',
                'total' => $venta->total,
                'direccion_entrega' => null,
                'telefono_contacto' => null,
                'notas' => $venta->notas,
                'atendido_por' => $venta->atendido_por,
                'caja_id' => $venta->caja_id,
                'metodo_pago' => $venta->metodo_pago,
                'monto_recibido' => $venta->monto_recibido,
                'cambio' => $venta->cambio,
                'pagado_at' => $venta->created_at,
                'created_at' => $venta->created_at,
                'updated_at' => $venta->updated_at,
            ]);

            $lineas = DB::table('venta_items')->where('venta_id', $venta->id)->orderBy('id')->get();

            foreach ($lineas as $linea) {
                DB::table('pedido_items')->insert([
                    'pedido_id' => $pedidoId,
                    'producto_id' => $linea->producto_id,
                    'cantidad' => $linea->cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                    'subtotal' => $linea->subtotal,
                    'created_at' => $linea->created_at,
                    'updated_at' => $linea->updated_at,
                ]);
            }
        }
    }
};