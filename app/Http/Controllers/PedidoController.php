<?php

namespace App\Http\Controllers;

use App\Http\Requests\PedidoEstadoRequestWeb;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;

/**
 * Pedidos de delivery.
 *
 * Esta tabla es sólo de delivery; lo que se consume en el salón se guarda en
 * Venta. Por eso aquí ya no hay ningún filtro por tipo: todo lo que sale de
 * Pedido es, por definición, un pedido a domicilio.
 *
 * Esta misma clase la usan los tres paneles que necesitan ver pedidos: el del
 * administrador, el del cajero y el de la cocina. Lo que cambia entre ellos no es
 * la operación, sino los permisos (los impone el middleware 'role' de las rutas)
 * y la ruta a la que se vuelve al terminar, que se decide con el rol de quien
 * está operando.
 */
class PedidoController extends Controller
{
    /**
     * Listado de pedidos de delivery, del más reciente al más antiguo.
     */
    public function index()
    {
        $pedidos = Pedido::with('user')
            ->latest('id')
            ->paginate(15);

        return view('pedidos.index', compact('pedidos'));
    }

    /**
     * Ficha de un pedido: qué pidió el cliente, a dónde va y en qué estado está.
     */
    public function show(Pedido $pedido)
    {
        $pedido->load(['user', 'items.producto', 'atendidoPor']);

        return view('pedidos.show', compact('pedido'));
    }

    /**
     * Cambio de estado de pedido.
     *
     * `atendido_por` se sobrescribe con el usuario autenticado y se registra el
     * cambio en el log. Las dos cosas son el punto 4 (trazabilidad) en la práctica:
     * un pedido debe poder responder en todo momento "quién lo movió y cuándo".
     *
     * `PedidoEstadoRequestWeb` valida la transición contra la tabla de estados
     * permitidos, así que un pedido entregado no puede volver a pendiente.
     */
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