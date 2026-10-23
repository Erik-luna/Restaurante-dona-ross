<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        $locales = DB::table('pedidos')->where('tipo', 'local')->orderBy('id')->get();

        foreach ($locales as $pedido) {
            $ventaId = DB::table('ventas')->insertGetId([
                'user_id' => $pedido->user_id,
                'caja_id' => $pedido->caja_id,
                'estado' => $pedido->estado === 'cancelado' ? 'anulada' : 'pagada',
                'total' => $pedido->total,
                'metodo_pago' => $pedido->metodo_pago ?? 'efectivo',
                'monto_recibido' => $pedido->monto_recibido,
                'cambio' => $pedido->cambio,
                'notas' => $pedido->notas,
                'atendido_por' => $pedido->atendido_por,
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
        $idsLocales = $locales->pluck('id')->all();

        if ($idsLocales !== []) {
            DB::table('pedido_items')->whereIn('pedido_id', $idsLocales)->delete();
            DB::table('pedidos')->whereIn('id', $idsLocales)->delete();
        }
        Schema::table('pedidos', function ($table) {
            $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
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