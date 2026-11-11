<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\StockJornada;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
class StockService
{
    public function descontar(array $items): void
    {
        foreach ($items as $item) {
            $producto = $item['producto'];
            $cantidad = (int) $item['cantidad'];

            if (! $producto->activo) {
                throw new RuntimeException('Uno de los productos seleccionados no se encuentra activo.');
            }

            $jornada = $this->bloquearJornadaDe($producto);

            if ($jornada->disponible() < $cantidad) {
                throw new RuntimeException(sprintf(
                    'Stock insuficiente para %s: quedan %d porción(es) y se pidieron %d.',
                    $producto->nombre,
                    $jornada->disponible(),
                    $cantidad
                ));
            }

            $jornada->consumir($cantidad);
        }
    }
    public function aplicar(
        Producto $producto,
        string $accion,
        int $cantidad,
        ?string $motivo = null,
        ?string $fecha = null,
        ?int $userId = null
    ): StockJornada {
        $jornada = $this->jornadaDe($producto, $fecha);

        return match ($accion) {
            StockJornada::ACCION_CARGAR => $jornada->fijarInicial($cantidad, $userId),
            StockJornada::ACCION_REPONER => $jornada->reponer($cantidad, $motivo, $userId),
            StockJornada::ACCION_MERMA => $jornada->registrarMerma($cantidad, (string) $motivo, $userId),
            default => throw new RuntimeException('Ajuste de stock desconocido: '.$accion),
        };
    }
    public function jornadaDe(Producto $producto, ?string $fecha = null): StockJornada
    {
        return StockJornada::obtenerOCrear($producto->id, $fecha);
    }
    public function bloquearJornadaDe(Producto $producto, ?string $fecha = null): StockJornada
    {
        $this->jornadaDe($producto, $fecha);

        try {
            return StockJornada::lockForUpdate()
                ->where('producto_id', $producto->id)
                ->whereDate('fecha', $fecha ?? now()->toDateString())
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new RuntimeException('No se pudo bloquear la jornada de stock de '.$producto->nombre.'.');
        }
    }

    /**
     * Devuelve al stock las porciones de un pedido cancelado, en la jornada
     * del día en que se hizo el pedido (que es donde se descontaron).
     *
     * Devuelve la cantidad de ítems que sí pudieron devolverse. Si no existe
     * jornada para la fecha, el pedido se cancela igual y se deja el aviso
     * en el log (no se inventa una jornada vacía).
     */
    public function devolverPedido(Pedido $pedido): int
    {
        return $this->devolver(
            $pedido->items()
                ->with('producto')
                ->get()
                ->map(fn ($item) => ['producto' => $item->producto, 'cantidad' => $item->cantidad])
                ->all(),
            $pedido->created_at->toDateString()
        );
    }

    public function devolver(array $items, ?string $fecha = null): int
    {
        $fecha = $fecha ?? now()->toDateString();
        $devueltos = 0;

        foreach ($items as $item) {
            $producto = $item['producto'] ?? null;
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if ($producto === null) {
                Log::warning('Se omitió la devolución de stock de un ítem sin producto.', [
                    'fecha' => $fecha,
                    'porciones' => $cantidad,
                ]);

                continue;
            }

            $jornada = StockJornada::lockForUpdate()
                ->where('producto_id', $producto->id)
                ->whereDate('fecha', $fecha)
                ->first();

            if ($jornada === null) {
                Log::warning('No hay jornada de stock para devolver las porciones del pedido cancelado.', [
                    'producto_id' => $producto->id,
                    'fecha' => $fecha,
                    'porciones' => $cantidad,
                ]);

                continue;
            }

            $jornada->devolver($cantidad);
            $devueltos++;
        }

        return $devueltos;
    }
}