<?php

namespace App\Http\Controllers;
use App\Http\Requests\DeliveryRequest;
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
