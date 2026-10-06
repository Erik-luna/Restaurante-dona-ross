<?php

namespace App\Http\Requests;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use Illuminate\Validation\Rule;

/**
 * Entrada o salida de efectivo que no es una venta: un retiro a la bóveda, un
 * cambio, un gasto del día.
 *
 * Sin este formulario el arqueo compararía el dinero contado contra las ventas y
 * aparecería un descuadre siempre que el restaurante tocara caja por otra razón,
 * aunque el conteo estuviera bien hecho.
 */
class CajaMovimientoRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->caja() !== null && $this->puedeOperarEstaCaja($this->caja());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', Rule::in([CajaMovimiento::ENTRADA, CajaMovimiento::SALIDA])],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'concepto' => ['required', 'string', 'min:5', 'max:150'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'tipo.in' => 'Elige si es una entrada o una salida de dinero.',
            'monto.min' => 'El monto debe ser mayor que cero.',
            'concepto.min' => 'Explica brevemente el movimiento (mínimo 5 caracteres).',
            'concepto.required' => 'Explica qué movimiento de caja es.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'monto_inicial' => 'monto inicial',
            'total_declarado' => 'dinero contado',
            'metodo_pago' => 'método de pago',
            'monto_recibido' => 'dinero recibido',
        ];
    }

    /**
     * Sólo se opera una caja abierta.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $caja = $this->caja();

                if ($caja !== null && ! $caja->estaAbierta()) {
                    $validator->errors()->add(
                        'monto',
                        'Esta caja ya está cerrada. No se pueden registrar movimientos en ella.'
                    );
                }
            },
        ];
    }

    /** La caja de la ruta. */
    public function caja(): ?Caja
    {
        $caja = $this->route('caja');

        return $caja instanceof Caja ? $caja : null;
    }

    /**
     * El cajero sólo opera su propia caja; el administrador puede operar cualquiera.
     */
    private function puedeOperarEstaCaja(Caja $caja): bool
    {
        return $this->user()?->esAdministrador() === true
            || ($this->user()?->esCajero() === true && $caja->user_id === $this->user()->id);
    }
}