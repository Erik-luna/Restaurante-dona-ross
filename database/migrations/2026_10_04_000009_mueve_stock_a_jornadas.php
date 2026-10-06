<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Traslada el contador único `productos.stock` a una jornada de hoy, y elimina
 * la columna.
 *
 * La migración de datos tiene un detalle que parece un error pero no lo es. Se
 * copia el valor de `productos.stock` a la columna `inicial` de la jornada, y a
 * la vez se guarda en `consumidos` lo vendido hoy. Como `productos.stock` ya está
 * descontado, copiarlo tal cual a `inicial` restaría dos veces lo mismo y el
 * disponible de hoy saldría más bajo de lo que está. Por eso `inicial` recibe
 * `stock + consumidos`: el número con el que arrancó el día.
 *
 * También se rellena `user_id` con el primer administrador, que es quien llevaba
 * estos ajustes antes de que existiera el registro: sin él, la jornada quedaría
 * sin responsable de su carga.
 */
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
                // El stock guardado ya está descontado, así que hay que devolverle
                // lo consumido hoy para reconstruir el punto de partida del día.
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

        // La columna desaparece: a partir de ahora el stock vive en la jornada.
        // Dejarla sería mantener dos números que deben coincidir y que acabarán
        // discrepando en cuanto alguien reponga por un sitio y descuente por otro.
        Schema::table('productos', function ($table) {
            $table->dropColumn('stock');
        });
    }

    /** Porciones de un producto que se vendieron hoy, por venta de salón o por delivery. */
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

    /**
     * Devuelve el contador a `productos.stock`.
     *
     * `stock_jornadas` NO se borra aquí: esa tabla la creó la migración 000008 y es
     * suya para destruirla. Si esta la eliminara, al volver a migrar hacia adelante
     * Laravel daría 000008 por aplicada, no volvería a crear la tabla, y 000009 se
     * fallaría al insertar en ella. Es el tipo de fallo que solo aparece cuando ya
     * se desplegó y alguien necesita deshacer.
     */
    public function down(): void
    {
        Schema::table('productos', function ($table) {
            $table->unsignedInteger('stock')->default(0);
        });

        // Se devuelve a cada producto el disponible de su jornada de hoy, que es lo
        // que significaba la columna antes.
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