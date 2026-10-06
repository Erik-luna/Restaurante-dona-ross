<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;

/**
 * Panel de la cocina.
 *
 * La cocina no vende: prepara. Necesita ver los pedidos de delivery a medida que
 * entran, moverlos de estado conforme avanza cada plato y ajustar las porciones
 * disponibles del día. Por eso no tiene acceso al catálogo ni a los clientes.
 */
class CocinaController extends Controller
{
    /** Pedidos que aún no salieron hacia el cliente. */
    private const EN_SALON = [
        Pedido::ESTADO_PENDIENTE,
        Pedido::ESTADO_CONFIRMADO,
        Pedido::ESTADO_PREPARANDO,
    ];

    public function dashboard()
    {
        $stats = [
            // Lo que hay que preparar ahora mismo.
            'por_preparar' => Pedido::where('estado', Pedido::ESTADO_PENDIENTE)->count(),

            'preparando' => Pedido::where('estado', Pedido::ESTADO_PREPARANDO)->count(),

            // Ya listos, esperando que el repartidor los lleve.
            'listos' => Pedido::where('estado', Pedido::ESTADO_CONFIRMADO)->count(),

            // Un plato con 5 porciones o menos queda marcado en el panel para que
            // el personal reponga antes de que se acabe. La subconsulta devuelve NULL
            // para un producto sin jornada, y un NULL no cumple la comparación, así
            // que un plato todavía sin cargar no se cuenta como "stock bajo" aquí:
            // aparece en la pantalla de stock, que sí lo muestra.
            'stock_bajo' => Producto::where('activo', true)
                ->whereRaw(Producto::EXPRESION_DISPONIBLE.' BETWEEN 0 AND 5', Producto::rangoDeFecha())
                ->count(),
        ];

        // La cola de trabajo: primero lo que está sin empezar, después lo que
        // está en preparación y por último lo que ya está listo para salir.
        // El CASE ordena por prioridad sin depender de FIELD(), que es de MySQL.
        $pedidos = Pedido::with(['user', 'items.producto'])
            ->whereIn('estado', self::EN_SALON)
            ->orderByRaw("CASE estado
                WHEN 'pendiente' THEN 1
                WHEN 'preparando' THEN 2
                ELSE 3
            END")
            ->oldest()
            ->paginate(10);

        return view('cocina.dashboard', compact('stats', 'pedidos'));
    }
}