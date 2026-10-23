<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use App\Support\Texto;

class DeliveryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'cliente' => ['required', ...Reglas::NOMBRE],
            'direccion' => ['required', ...Reglas::DIRECCION],
            'telefono' => ['required', ...Reglas::TELEFONO],
            'pedido' => ['required', 'string', 'max:255'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'cliente.required' => 'El nombre completo es obligatorio.',
            'cliente.regex' => 'El nombre del cliente no debe contener caracteres extraños ni código.',
            'direccion.required' => 'Debes proporcionar la dirección exacta de entrega.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'telefono.required' => 'El número de teléfono es indispensable para contactarte.',
            'telefono.regex' => 'El formato del teléfono ingresado no es válido.',
            'pedido.required' => 'Debes seleccionar el platillo que deseas ordenar.',
        ];
    }
}
