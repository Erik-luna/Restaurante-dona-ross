<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Venta;

/**
 * Panel del cajero.
 *
 * El cajero atiende a quien consume en el salón: abre su caja al empezar el turno,
 * registra las ventas presenciales y cobra los pedidos de delivery ya entregados.
 * Al terminar el turno cierra la caja con su arqueo.
 */
class CajeroController extends Controller
{
    public function dashboard()
    {
        // La caja del turno. Puede ser null si todavía no la abrió: la vista lo
        // muestra como aviso en lugar de romperse.
        $caja = Caja::abiertaDeHoy(auth()->id());

        $stats = [
            // Las ventas del salón que este cajero ha registrado hoy.
            'ventas_hoy' => Venta::where('atendido_por', auth()->id())
                ->whereDate('created_at', today())
                ->sum('total'),

            'pedidos_hoy' => Venta::where('atendido_por', auth()->id())
                ->whereDate('created_at', today())
                ->count(),

            // Dinero que hay que cobrar: pedidos no cancelados y todavía sin
            // cobrar. Son los de delivery, que es de donde sale el plato que se
            // come en la calle. Las ventas de salón no aparecen porque nacen ya
            // cobradas al pagarse en el mostrador.
            //
            // Antes este número usaba los estados 'pendiente' y 'en_preparacion'.
            // 'en_preparacion' no existe: el enum de estados tiene 'preparando'.
            // Y una venta de salón nace ya cobrada, así que se contaban pedidos
            // que nadie iba a cobrar nunca y el total salía en cero.
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