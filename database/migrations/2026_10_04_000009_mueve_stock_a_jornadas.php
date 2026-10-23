<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        $hoy = now()->toDateString();

        $adminId = DB::table('users')
            ->where('role', 'administrador')
            ->orderBy('id')
            ->value('id');

        foreach (DB::table('productos')->orderBy('id')->get() as $producto) {
            $stock = (int) $producto->stock;

            $consumidos = $this->consumidosDelDia($producto->id, $hoy);

            DB::table('stock_jornadas')->insert([
                'producto_id' => $producto->id,
                'fecha' => $hoy,
                'inicial' => $stock + $consumidos,
                'entradas' => 0,
                'mermas' => 0,
                'consumidos' => $consumidos,
                'motivo' => 'Carga inicial al migrar el contador único de stock.',
                'user_id' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Schema::table('productos', function ($table) {
            $table->dropColumn('stock');
        });
    }
    private function consumidosDelDia(int $productoId, string $fecha): int
    {
        $ventas = DB::table('venta_items')
            ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
            ->where('venta_items.producto_id', $productoId)
            ->whereDate('ventas.created_at', $fecha)
            ->sum('venta_items.cantidad');

        $pedidos = DB::table('pedido_items')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->where('pedido_items.producto_id', $productoId)
            ->whereDate('pedidos.created_at', $fecha)
            ->sum('pedido_items.cantidad');

        return (int) $ventas + (int) $pedidos;
    }
    public function down(): void
    {
        Schema::table('productos', function ($table) {
            $table->unsignedInteger('stock')->default(0);
        });
        foreach (DB::table('stock_jornadas')->whereDate('fecha', now()->toDateString())->get() as $jornada) {
            DB::table('productos')
                ->where('id', $jornada->producto_id)
                ->update([
                    'stock' => max(
                        0,
                        (int) $jornada->inicial + (int) $jornada->entradas
                        - (int) $jornada->mermas - (int) $jornada->consumidos
                    ),
                ]);
        }
    }
};