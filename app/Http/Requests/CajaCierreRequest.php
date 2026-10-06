<?php

namespace App\Http\Requests;

use App\Models\Caja;

/**
 * Cierre de caja: conteo del dinero y arqueo.
 */
class CajaCierreRequest extends BaseRequest
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
            'total_declarado' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'observacion_cierre' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'total_declarado.required' => 'Indica cuánto dinero hay en la caja al contarlo.',
            'total_declarado.min' => 'El dinero contado no puede ser negativo.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $caja = $this->caja();

                if ($caja !== null && ! $caja->estaAbierta()) {
                    $validator->errors()->add(
                        'total_declarado',
                        'Esta caja ya se cerró antes. No se puede cerrar dos veces.'
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

    private function puedeOperarEstaCaja(Caja $caja): bool
    {
        return $this->user()?->esAdministrador() === true
            || ($this->user()?->esCajero() === true && $caja->user_id === $this->user()->id);
    }
}