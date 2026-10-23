<?php

namespace App\Http\Requests;

use App\Models\Pedido;
use Illuminate\Validation\Rule;

class PedidoEstadoRequestWeb extends BaseRequest
{
    public const ESTADOS = Pedido::ESTADOS;
    public const TRANSICIONES = [
        Pedido::ESTADO_PENDIENTE => [Pedido::ESTADO_PENDIENTE],
        Pedido::ESTADO_CONFIRMADO => [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CONFIRMADO],
        Pedido::ESTADO_PREPARANDO => [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CONFIRMADO, Pedido::ESTADO_PREPARANDO],
        Pedido::ESTADO_ENVIADO => [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CONFIRMADO, Pedido::ESTADO_PREPARANDO, Pedido::ESTADO_ENVIADO],
        Pedido::ESTADO_ENTREGADO => [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CONFIRMADO, Pedido::ESTADO_PREPARANDO, Pedido::ESTADO_ENVIADO, Pedido::ESTADO_ENTREGADO],
        Pedido::ESTADO_CANCELADO => [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CONFIRMADO, Pedido::ESTADO_PREPARANDO, Pedido::ESTADO_ENVIADO, Pedido::ESTADO_CANCELADO],
    ];

    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCocina() === true;
    }
    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::in(self::ESTADOS)],
        ];
    }
    protected function mensajesPropios(): array
    {
        return [
            'estado.in' => 'El estado indicado no es válido.',
        ];
    }
    public function after(): array
    {
        return [
            function ($validator) {
                $pedido = $this->pedido();
                $nuevo = (string) $this->input('estado');

                if ($pedido === null || $nuevo === '') {
                    return;
                }
                if ($pedido->estado === $nuevo) {
                    return;
                }

                $permitidos = self::TRANSICIONES[$nuevo] ?? [];

                if (! in_array($pedido->estado, $permitidos, true)) {
                    $validator->errors()->add(
                        'estado',
                        'No se puede pasar de "'.$pedido->estado.'" a "'.$nuevo.'".'
                    );
                }
            },
        ];
    }
    public function pedido(): ?Pedido
    {
        $pedido = $this->route('pedido');

        return $pedido instanceof Pedido ? $pedido : null;
    }
}