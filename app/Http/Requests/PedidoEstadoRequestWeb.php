<?php

namespace App\Http\Requests;

use App\Models\Pedido;
use Illuminate\Validation\Rule;

/**
 * Cambio de estado de un pedido desde el panel web.
 *
 * Además de comprobar que el estado sea uno de los válidos, se comprueba que el
 * cambio tenga sentido: un pedido ya entregado no puede volver a "pendiente".
 * Sin esa comprobación se falsearían las ventas del día y se podría reabrir un
 * pedido cancelado saltándose la caja.
 */
class PedidoEstadoRequestWeb extends BaseRequest
{
    /**
     * Estados por los que puede pasar un pedido.
     *
     * Se leen del modelo y no se escriben aquí otra vez: si se copiaran, un
     * estado nuevo podría añadirse al modelo y quedarse fuera de la validación
     * (o al revés) y el formulario dejaría de aceptar un estado legal.
     */
    public const ESTADOS = Pedido::ESTADOS;

    /**
     * Desde qué estados se puede llegar a cada estado.
     *
     * El orden de cada lista va del estado más avanzado al más retrasado: así, un
     * pedido ya entregado no puede volver a "pendiente" porque "pendiente" no
     * está en la lista de destinos de "entregado".
     */
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
        // Mueven un pedido el administrador y la cocina. El cajero sólo consulta: no
        // decide en qué estado está la comida.
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCocina() === true;
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * Comprobación de la transición: sólo se puede pasar de un estado a otro si el
     * cambio está en la tabla TRANSICIONES.
     *
     * Va en after() y no en rules() porque hace falta leer el estado actual del
     * pedido, que no viene en el formulario.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $pedido = $this->pedido();
                $nuevo = (string) $this->input('estado');

                if ($pedido === null || $nuevo === '') {
                    return;
                }

                // Repetir el mismo estado no es un salto hacia atrás, es una
                // corrección, así que siempre se permite.
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

    /**
     * El pedido de la ruta, ya resuelto por Laravel.
     *
     * La ruta es /pedidos/{pedido}/estado, así que Laravel entrega el modelo.
     */
    public function pedido(): ?Pedido
    {
        $pedido = $this->route('pedido');

        return $pedido instanceof Pedido ? $pedido : null;
    }
}