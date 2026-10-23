<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;

class CocinaController extends Controller
{
    private const EN_SALON = [
        Pedido::ESTADO_PENDIENTE,
        Pedido::ESTADO_CONFIRMADO,
        Pedido::ESTADO_PREPARANDO,
    ];

    public function dashboard()
    {
        $stats = [
            'por_preparar' => Pedido::where('estado', Pedido::ESTADO_PENDIENTE)->count(),
            'preparando' => Pedido::where('estado', Pedido::ESTADO_PREPARANDO)->count(),
            'listos' => Pedido::where('estado', Pedido::ESTADO_CONFIRMADO)->count(),
            'stock_bajo' => Producto::where('activo', true)
                ->whereRaw(Producto::EXPRESION_DISPONIBLE.' BETWEEN 0 AND 5', Producto::rangoDeFecha())
                ->count(),
        ];

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