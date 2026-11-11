<?php

namespace App\Http\Controllers;

use App\Http\Requests\PedidoEstadoRequestWeb;
use App\Models\Pedido;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with('user')
            ->latest('id')
            ->paginate(15);

        return view('pedidos.index', compact('pedidos'));
    }

    public function show(Pedido $pedido)
    {
        $pedido->load(['user', 'items.producto', 'atendidoPor']);

        return view('pedidos.show', compact('pedido'));
    }

    public function updateEstado(PedidoEstadoRequestWeb $request, Pedido $pedido, StockService $stock)
    {
        $estadoAnterior = $pedido->estado;
        $estadoNuevo = (string) $request->validated('estado');
        $itemsDevueltos = 0;

        $aplicarCambio = function () use ($pedido, $estadoNuevo): void {
            $pedido->forceFill([
                'estado' => $estadoNuevo,
                'atendido_por' => auth()->id(),
            ])->save();
        };

        $esCancelacion = $estadoNuevo === Pedido::ESTADO_CANCELADO
            && $estadoAnterior !== Pedido::ESTADO_CANCELADO;

        if ($esCancelacion) {
            // El cambio de estado y la devolución de stock van juntos: si algo
            // falla, el pedido no queda cancelado sin devolver las porciones.
            DB::transaction(function () use ($aplicarCambio, $pedido, $stock, &$itemsDevueltos) {
                $aplicarCambio();

                $itemsDevueltos = $stock->devolverPedido($pedido);
            });
        } else {
            $aplicarCambio();
        }

        Log::info('Estado de pedido modificado.', [
            'pedido_id' => $pedido->id,
            'desde' => $estadoAnterior,
            'hacia' => $estadoNuevo,
            'usuario_id' => auth()->id(),
            'ip' => $request->ip(),
            'items_devueltos' => $itemsDevueltos,
        ]);

        return back()->with('success', 'Estado del pedido actualizado correctamente.');
    }
}