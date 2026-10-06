<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\StockJornada;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

/**
 * El único lugar del sistema donde el stock baja.
 *
 * Existe porque descontar stock aparece en dos operaciones distintas —confirmar un
 * pedido de delivery y registrar una venta de salón— y las dos necesitan lo mismo:
 * bloquear la jornada, comprobar que quedan porciones, descontar y avisar con un
 * error de negocio si no quedan.
 *
 * Duplicar ese bloque en los dos controladores es la forma más rápida de que una de
 * las dos rutas se quede sin la comprobación y termine vendiendo algo que ya no
 * existe. Este servicio es ese lugar único.
 */
class StockService
{
    /**
     * Descuenta porciones por una venta de salón o por un pedido de delivery.
     *
     * @param  array<int, array{producto: Producto, cantidad: int}>  $items
     *
     * @throws RuntimeException si algún producto no tiene porciones suficientes.
     */
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

    /**
     * Aplica un ajuste manual a la jornada de un producto.
     *
     * Es el punto de entrada del formulario de stock, y por eso recibe la acción en
     * lugar de tener un método por acción: el request ya la validó contra las tres
     * constantes de StockJornada, y volver a comprobarla aquí sería repetir la
     * validación sin añadir nada. Lo que sí hace el request —exigir motivo en las
     * mermas— es responsabilidad suya.
     */
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

    /**
     * La jornada de un producto para una fecha; si no existe se crea vacía.
     *
     * Crear la fila vacía aquí es lo que permite que el carrito y la venta
     * trabajen contra una jornada real siempre. Y hacerlo con `obtenerOCrear()` y
     * no con `abrir()` es lo que impide que una compra borre la carga que la cocina
     * hizo esa mañana.
     */
    public function jornadaDe(Producto $producto, ?string $fecha = null): StockJornada
    {
        return StockJornada::obtenerOCrear($producto->id, $fecha);
    }

    /**
     * La jornada de hoy, bloqueada para escritura.
     *
     * El bloqueo es lo que evita que dos cajas vendan las mismas últimas dos
     * porciones: sin él, las dos leerían "queda 2" y las dos venderían 2. Y tiene que
     * aplicarse sobre la fila de la jornada, que es la que lleva la cuenta, y no
     * sobre el producto: bloquear el producto ya no protege nada, porque el número
     * que se decrementa no está en esa tabla.
     */
    public function bloquearJornadaDe(Producto $producto, ?string $fecha = null): StockJornada
    {
        $this->jornadaDe($producto, $fecha);

        try {
            return StockJornada::lockForUpdate()
                ->where('producto_id', $producto->id)
                ->whereDate('fecha', $fecha ?? now()->toDateString())
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            // Sólo alcanzable si otra transacción borró la fila entre `jornadaDe()` y
            // el bloqueo. Es una carrera impensable en el servicio, así que se relanza
            // con un mensaje claro en vez de devolver stock fantasma.
            throw new RuntimeException('No se pudo bloquear la jornada de stock de '.$producto->nombre.'.');
        }
    }
}