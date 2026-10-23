<?php

namespace App\Http\Requests;

use App\Models\Caja;

class CajaAperturaRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCajero() === true;
    }
    public function rules(): array
    {
        return [
            'monto_inicial' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'observacion_apertura' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'monto_inicial.required' => 'Indica con cuánto dinero abre la caja.',
            'monto_inicial.min' => 'El monto inicial no puede ser negativo.',
        ];
    }
    public function after(): array
    {
        return [
            function ($validator) {
                $yaHayCajaAbierta = Caja::where('user_id', $this->user()->id)
                    ->where('estado', Caja::ABIERTA)
                    ->exists();

                if ($yaHayCajaAbierta) {
                    $validator->errors()->add(
                        'monto_inicial',
                        'Ya tienes una caja abierta. Ciérrala con su arqueo antes de abrir otra.'
                    );
                }
            },
        ];
    }
}