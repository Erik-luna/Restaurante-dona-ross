<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use App\Support\Texto;

/**
 * Formulario público de pedido a domicilio.
 *
 * No requiere autenticación, así que es la entrada más expuesta del proyecto:
 * cualquier visitante, y por tanto cualquier bot, puede invocarla.
 *
 * Las reglas de `cliente` y `telefono` son listas blancas estrictas porque ambos
 * campos se leen en voz alta por teléfono al cliente real: un `<script>` en el
 * nombre no aporta nada y sólo introduce riesgo. `pedido` y `direccion` admiten
 * texto libre acotado, porque una dirección legítima puede contener casi todo.
 */
class DeliveryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
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
