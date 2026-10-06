<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryRequest;

/**
 * Pedido rápido de delivery desde la página pública.
 *
 * A diferencia del carrito del cliente registrado, aquí no se guarda nada en la
 * base de datos: es una consulta que el restaurante atiende por teléfono. Los
 * datos se validan con DeliveryRequest y se muestran como confirmación.
 */
class DeliveryController extends Controller
{
    public function index()
    {
        return view('delivery');
    }

    public function store(DeliveryRequest $request)
    {
        $datos = $request->validated();

        return back()
            ->withInput($request->except('pedido'))
            ->with('success', '¡Excelente! Tu pedido a domicilio en Doña Ross ha sido registrado y está en camino.')
            ->with('delivery_referencia', [
                'cliente' => $datos['cliente'],
                'platillo' => $datos['pedido'],
            ]);
    }
}
