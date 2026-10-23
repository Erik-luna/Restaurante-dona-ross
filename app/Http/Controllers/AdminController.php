<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'productos' => Producto::count(),
            'clientes' => User::where('role', User::ROL_CLIENTE)->count(),
            'cajeros' => User::where('role', User::ROL_CAJERO)->count(),
            'cocina' => User::where('role', User::ROL_COCINA)->count(),
            'pedidos_hoy' => Pedido::whereDate('created_at', today())->count(),
            'ventas_hoy' => Venta::whereDate('created_at', today())->sum('total'),
            'pedidos_pendientes' => Pedido::where('estado', Pedido::ESTADO_PENDIENTE)->count(),
        ];

        $pedidosRecientes = Pedido::with('user')->latest('id')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'pedidosRecientes'));
    }
}
