<?php

namespace Tests;

use App\Models\Producto;
use App\Models\StockJornada;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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

    protected function cargarStock(Producto $producto, int $cantidad, ?string $fecha = null): StockJornada
    {
        $jornada = StockJornada::obtenerOCrear($producto->id, $fecha);
        $jornada->forceFill(['inicial' => $cantidad])->save();

        return $jornada;
    }
}