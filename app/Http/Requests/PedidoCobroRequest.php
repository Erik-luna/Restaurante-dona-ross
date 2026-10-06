<?php

namespace App\Http\Requests;

use App\Models\Caja;
use App\Models\Pedido;
use Illuminate\Validation\Rule;

/**
 * Confirmación de cobro de un pedido.
 *
 * El dinero que entra por aquí se guarda en la caja abierta, así que el cobro y
 * la caja no se pueden registrar por separado: si el pedido queda pagado, el
 * sistema tiene que saber en qué caja entró ese dinero.
 */
class PedidoCobroRequest extends BaseRequest
{
    public function authorize(): bool
    {
        // Cobra el administrador y el cajero. La cocina prepara la comida, pero no
        // hace caja.
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCajero() === true;
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'monto_recibido' => 'dinero recibido',
            'metodo_pago' => 'método de pago',
        ];
    }

    /**
     * Reglas que dependen del pedido, del método de pago y de si hay caja abierta.
     *
     * @return array<int, callable>
     */
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

                // Sin caja abierta el dinero cobraría flotando: el pedido constaría
                // como pagado pero no aparecería en el arqueo de ninguna caja, que es
                // justo el descuadre que el módulo de caja existe para evitar.
                if ($this->cajaAbierta() === null) {
                    $validator->errors()->add(
                        'metodo_pago',
                        'No tienes una caja abierta hoy. Ábrela antes de registrar un cobro.'
                    );

                    return;
                }

                // Con efectivo hay que saber cuánto entregó el cliente para poder
                // calcular el cambio. Con QR o tarjeta el dinero entra exacto, así
                // que el campo no aplica y se deja vacío.
                if ($this->input('metodo_pago') === Pedido::PAGO_EFECTIVO) {
                    $this->validarEfectivo($validator, $pedido);
                }
            },
        ];
    }

    /** La caja abierta donde entrará el dinero. */
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

        // Entregar menos de lo que vale el pedido no es un cobro: es una deuda.
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