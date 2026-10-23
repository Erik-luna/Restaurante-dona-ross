<?php

namespace App\Http\Requests;

use App\Models\Caja;
use App\Models\Pedido;
use Illuminate\Validation\Rule;

class PedidoCobroRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCajero() === true;
    }
    public function rules(): array
    {
        return [
            'metodo_pago' => ['required', 'string', Rule::in(Pedido::METODOS_PAGO)],
            'monto_recibido' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'metodo_pago.in' => 'El método de pago indicado no es válido.',
            'metodo_pago.required' => 'Indica cómo pagó el cliente.',
            'monto_recibido.numeric' => 'El dinero recibido debe ser un número.',
        ];
    }
    public function attributes(): array
    {
        return [
            'monto_recibido' => 'dinero recibido',
            'metodo_pago' => 'método de pago',
        ];
    }
    public function after(): array
    {
        return [
            function ($validator) {
                $pedido = $this->pedido();

                if ($pedido === null) {
                    return;
                }

                if ($pedido->estaCobrado()) {
                    $validator->errors()->add('metodo_pago', 'Este pedido ya fue cobrado antes.');

                    return;
                }

                if ($pedido->estado === Pedido::ESTADO_CANCELADO) {
                    $validator->errors()->add('metodo_pago', 'No se puede cobrar un pedido cancelado.');

                    return;
                }
                if ($this->cajaAbierta() === null) {
                    $validator->errors()->add(
                        'metodo_pago',
                        'No tienes una caja abierta hoy. Ábrela antes de registrar un cobro.'
                    );

                    return;
                }
                if ($this->input('metodo_pago') === Pedido::PAGO_EFECTIVO) {
                    $this->validarEfectivo($validator, $pedido);
                }
            },
        ];
    }
    public function cajaAbierta(): ?Caja
    {
        return Caja::abiertaDeHoy((int) $this->user()->id);
    }

    private function validarEfectivo($validator, Pedido $pedido): void
    {
        $recibido = $this->input('monto_recibido');

        if ($recibido === null || $recibido === '') {
            $validator->errors()->add('monto_recibido', 'Indica cuánto dinero entregó el cliente.');

            return;
        }
        if ((float) $recibido < (float) $pedido->total) {
            $valor = number_format((float) $pedido->total, 2);

            $validator->errors()->add(
                'monto_recibido',
                'El cliente entregó menos que el total de Bs. '.$valor.'.'
            );
        }
    }

    public function pedido(): ?Pedido
    {
        $pedido = $this->route('pedido');

        return $pedido instanceof Pedido ? $pedido : null;
    }
}