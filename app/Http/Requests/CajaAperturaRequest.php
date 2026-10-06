<?php

namespace App\Http\Requests;

use App\Models\Caja;

/**
 * Apertura de caja al empezar el turno.
 */
class CajaAperturaRequest extends BaseRequest
{
    public function authorize(): bool
    {
        // Abre caja el administrador y el cajero. La cocina no maneja dinero en
        // efectivo, así que ni siquiera puede intentarlo.
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCajero() === true;
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * Comprobación de que la persona no tiene ya una caja abierta.
     *
     * Lo que se comprueba es que no tenga una caja ABIERTA, no que no haya
     * trabajado hoy: si el turno se parte en dos, tras cerrar la primera caja debe
     * poder abrir otra para el segundo turno.
     *
     * Sin esta comprobación el índice de la base de datos no saltaría (ya no es
     * único a propósito), así que hace falta aquí para devolver un mensaje que el
     * usuario entienda en lugar de crear dos cajas abiertas simultáneas que
     * partirían el dinero del turno en dos arqueos.
     *
     * @return array<int, callable>
     */
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