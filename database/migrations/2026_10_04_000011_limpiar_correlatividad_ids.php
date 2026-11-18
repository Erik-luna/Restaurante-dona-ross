<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('productos')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // 1. Limpiar productos antiguos inactivos que fueron reemplazados por el catálogo oficial
        $productosActivos = DB::table('productos')
            ->where('activo', 1)
            ->orderBy('id', 'asc')
            ->get();

        if ($productosActivos->isNotEmpty() && $productosActivos->first()->id > 1) {
            // Mapeo de IDs viejos (ej. 12..24) a correlativos (1..13)
            $mapa = [];
            $nuevoId = 1;
            foreach ($productosActivos as $prod) {
                $mapa[$prod->id] = $nuevoId++;
            }

            // Remapear pedidos y ventas que apuntaban a productos viejos 1..5 hacia los productos oficiales 1..5
            DB::table('pedido_items')->where('producto_id', 1)->update(['producto_id' => $mapa[12] ?? 1]);
            DB::table('pedido_items')->where('producto_id', 2)->update(['producto_id' => $mapa[13] ?? 2]);
            DB::table('pedido_items')->where('producto_id', 3)->update(['producto_id' => $mapa[14] ?? 3]);
            DB::table('pedido_items')->where('producto_id', 4)->update(['producto_id' => $mapa[15] ?? 4]);
            DB::table('pedido_items')->where('producto_id', 5)->update(['producto_id' => $mapa[16] ?? 5]);

            DB::table('venta_items')->where('producto_id', 3)->update(['producto_id' => $mapa[14] ?? 3]);
            DB::table('venta_items')->where('producto_id', 4)->update(['producto_id' => $mapa[15] ?? 4]);
            DB::table('venta_items')->where('producto_id', 5)->update(['producto_id' => $mapa[16] ?? 5]);

            // Eliminar productos inactivos huérfanos y su stock viejo
            $idsActivos = $productosActivos->pluck('id')->all();
            DB::table('stock_jornadas')->whereNotIn('producto_id', $idsActivos)->delete();
            DB::table('productos')->whereNotIn('id', $idsActivos)->delete();

            // Actualizar IDs de productos activos a 1..N
            foreach ($mapa as $viejoId => $nId) {
                DB::table('productos')->where('id', $viejoId)->update(['id' => $nId]);
                DB::table('pedido_items')->where('producto_id', $viejoId)->update(['producto_id' => $nId]);
                DB::table('venta_items')->where('producto_id', $viejoId)->update(['producto_id' => $nId]);
                DB::table('stock_jornadas')->where('producto_id', $viejoId)->update(['producto_id' => $nId]);
            }

            $maxProd = DB::table('productos')->max('id') ?? 0;
            DB::statement('ALTER TABLE productos AUTO_INCREMENT = ' . ($maxProd + 1) . ';');
        }

        // 2. Correlatividad de usuarios (ej. usuario 8 cocina a 7)
        $usuario8 = DB::table('users')->where('id', 8)->first();
        if ($usuario8 && ! DB::table('users')->where('id', 7)->exists()) {
            DB::table('pedidos')->where('user_id', 8)->update(['user_id' => 7]);
            DB::table('pedidos')->where('atendido_por', 8)->update(['atendido_por' => 7]);
            DB::table('ventas')->where('user_id', 8)->update(['user_id' => 7]);
            DB::table('ventas')->where('atendido_por', 8)->update(['atendido_por' => 7]);
            DB::table('cajas')->where('user_id', 8)->update(['user_id' => 7]);
            DB::table('stock_jornadas')->where('user_id', 8)->update(['user_id' => 7]);
            DB::table('users')->where('id', 8)->update(['id' => 7]);
            $maxUser = DB::table('users')->max('id') ?? 0;
            DB::statement('ALTER TABLE users AUTO_INCREMENT = ' . ($maxUser + 1) . ';');
        }

        // 3. Correlatividad de pedidos
        $pedidos = DB::table('pedidos')->orderBy('id', 'asc')->get();
        if ($pedidos->isNotEmpty()) {
            $nuevoPedidoId = 1;
            foreach ($pedidos as $p) {
                if ($p->id !== $nuevoPedidoId) {
                    DB::table('pedido_items')->where('pedido_id', $p->id)->update(['pedido_id' => $nuevoPedidoId]);
                    DB::table('pedidos')->where('id', $p->id)->update(['id' => $nuevoPedidoId]);
                }
                $nuevoPedidoId++;
            }
            $maxPedido = DB::table('pedidos')->max('id') ?? 0;
            DB::statement('ALTER TABLE pedidos AUTO_INCREMENT = ' . ($maxPedido + 1) . ';');
        }

        // 4. Correlatividad de pedido_items
        $items = DB::table('pedido_items')->orderBy('id', 'asc')->get();
        if ($items->isNotEmpty()) {
            $nuevoItemId = 1;
            foreach ($items as $it) {
                if ($it->id !== $nuevoItemId) {
                    DB::table('pedido_items')->where('id', $it->id)->update(['id' => $nuevoItemId]);
                }
                $nuevoItemId++;
            }
            $maxItem = DB::table('pedido_items')->max('id') ?? 0;
            DB::statement('ALTER TABLE pedido_items AUTO_INCREMENT = ' . ($maxItem + 1) . ';');
        }

        // 5. Correlatividad de stock_jornadas
        $stocks = DB::table('stock_jornadas')->orderBy('id', 'asc')->get();
        if ($stocks->isNotEmpty()) {
            $nuevoStockId = 1;
            foreach ($stocks as $st) {
                if ($st->id !== $nuevoStockId) {
                    DB::table('stock_jornadas')->where('id', $st->id)->update(['id' => $nuevoStockId]);
                }
                $nuevoStockId++;
            }
            $maxStock = DB::table('stock_jornadas')->max('id') ?? 0;
            DB::statement('ALTER TABLE stock_jornadas AUTO_INCREMENT = ' . ($maxStock + 1) . ';');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        // No se requiere revertir limpieza de correlatividad
    }
};
