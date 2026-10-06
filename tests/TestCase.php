<?php

namespace Tests;

use App\Models\Producto;
use App\Models\StockJornada;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea un producto con su carga de stock de HOY.
     *
     * Antes de la FASE 4 el stock era una columna del producto y bastaba con
     * `Producto::create(['stock' => 20])`. Ahora el stock es una jornada, así que un
     * producto sin carga no se puede vender ni aparecer en el catálogo. Si los tests
     * siguieran creando productos "sueltos", todos los que dependan de que haya
     * existencias fallarían por una razón que no tiene nada que ver con lo que
     * están probando.
     *
     * Por eso el helper abre la jornada junto con el producto: si un test necesita
     * un producto vendible, ese es su estado natural, y obtenerlo en dos líneas
     * separadas sería una forma de que uno de los dos pasos se olvide.
     *
     * @param  array<string, mixed>  $atributos
     */
    protected function crearProducto(int $stock = 20, array $atributos = []): Producto
    {
        $producto = Producto::create(array_merge([
            'nombre' => 'Silpancho Tradicional',
            'descripcion' => 'Plato de la casa',
            'precio' => 35.00,
            'activo' => true,
            'destacado' => false,
        ], $atributos));

        if ($stock > 0) {
            StockJornada::obtenerOCrear($producto->id)->fijarInicial($stock);
        }

        return $producto->fresh();
    }

    /** Fija una cantidad concreta para una fecha, útil para probar días pasados. */
    protected function cargarStock(Producto $producto, int $cantidad, ?string $fecha = null): StockJornada
    {
        $jornada = StockJornada::obtenerOCrear($producto->id, $fecha);
        $jornada->forceFill(['inicial' => $cantidad])->save();

        return $jornada;
    }
}