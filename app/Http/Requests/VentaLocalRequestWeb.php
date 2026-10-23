<?php

namespace App\Http\Requests;

use App\Models\Venta;
use Illuminate\Validation\Rule;

class VentaLocalRequestWeb extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esCajero() === true
            || $this->user()?->esAdministrador() === true;
    }
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'cliente')->where('activo', true)],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:50'],
            'notas' => ['nullable', 'string', 'max:500'],
            'metodo_pago' => ['nullable', 'string', Rule::in(Venta::METODOS_PAGO)],
            'monto_recibido' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'user_id.exists' => 'El cliente seleccionado no existe, no está activo o no es un cliente.',
            'items.required' => 'La venta debe incluir al menos un producto.',
            'items.min' => 'La venta debe incluir al menos un producto.',
            'items.*.producto_id.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.cantidad.min' => 'Cada producto debe tener al menos 1 unidad.',
            'notas.max' => 'Las notas no pueden superar los 500 caracteres.',
            'metodo_pago.in' => 'El método de pago indicado no es válido.',
            'monto_recibido.numeric' => 'El dinero recibido debe ser un número.',
        ];
    }
    public function attributes(): array
    {
        return [
            'user_id' => 'cliente',
            'notas' => 'notas',
            'items' => 'productos de la venta',
            'metodo_pago' => 'método de pago',
            'monto_recibido' => 'dinero recibido',
        ];
    }
}