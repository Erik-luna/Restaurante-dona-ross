<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Venta;

class CajeroController extends Controller
{
    public function dashboard()
    {

        $caja = Caja::abiertaDeHoy(auth()->id());

        $stats = [
            'ventas_hoy' => Venta::where('atendido_por', auth()->id())
                ->whereDate('created_at', today())
                ->sum('total'),

            'pedidos_hoy' => Venta::where('atendido_por', auth()->id())
                ->whereDate('created_at', today())
                ->count(),
            'por_cobrar' => Pedido::whereNull('pagado_at')
                ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
                ->count(),

            'por_cobrar_monto' => Pedido::whereNull('pagado_at')
                ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
                ->sum('total'),

            'clientes' => User::where('role', User::ROL_CLIENTE)->count(),
        ];

        $ultimasVentas = Venta::with('user')
            ->where('atendido_por', auth()->id())
            ->latest('id')
            ->take(5)
            ->get();

        return view('cajero.dashboard', compact('stats', 'ultimasVentas', 'caja'));
    }
}