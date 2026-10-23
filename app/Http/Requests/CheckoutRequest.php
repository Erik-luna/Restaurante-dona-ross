<?php

namespace App\Http\Requests;

use App\Support\Reglas;

class CheckoutRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esCliente() === true;
    }

    public function rules(): array
    {
        return [
            'direccion_entrega' => ['required', ...Reglas::DIRECCION],
            'telefono_contacto' => ['required', ...Reglas::TELEFONO],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'direccion_entrega.required' => 'La dirección de entrega es obligatoria.',
            'direccion_entrega.min' => 'La dirección debe tener al menos 5 caracteres.',
            'telefono_contacto.required' => 'El teléfono de contacto es necesario.',
            'telefono_contacto.regex' => 'El formato del teléfono es inválido.',
            'notas.max' => 'Las notas de cocina no pueden exceder 500 caracteres.',
        ];
    }
}
