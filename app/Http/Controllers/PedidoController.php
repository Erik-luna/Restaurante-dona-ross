<?php

namespace App\Http\Controllers;

use App\Http\Requests\PedidoEstadoRequestWeb;
use App\Models\Pedido;
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

    public function updateEstado(PedidoEstadoRequestWeb $request, Pedido $pedido)
    {
        $estadoAnterior = $pedido->estado;
        $estadoNuevo = (string) $request->validated('estado');

        $pedido->forceFill([
            'estado' => $estadoNuevo,
            'atendido_por' => auth()->id(),
        ])->save();

        Log::info('Estado de pedido modificado.', [
            'pedido_id' => $pedido->id,
            'desde' => $estadoAnterior,
            'hacia' => $estadoNuevo,
            'usuario_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        return back()->with('success', 'Estado del pedido actualizado correctamente.');
    }
}