<?php

namespace App\Http\Requests;

use App\Models\StockJornada;
use Illuminate\Validation\Rule;

/**
 * Ajuste del stock de un producto para un día, desde el panel web.
 *
 * No se pide un número absoluto de existencias. Un número absoluto obliga a quien
 * ajusta a conocer el total que el sistema ya tiene y a escribirlo de nuevo a
 * mano; si se equivoca de un número, el error queda oculto hasta el arqueo del
 * turno. En lugar de eso el formulario pide una acción y una cantidad:
 *
 *   cargar  → cuánto se preparó hoy (columna `inicial`)
 *   reponer → cuántas llegaron del proveedor durante el servicio (columna `entradas`)
 *   merma   → cuántas se perdieron (columna `mermas`, y exige motivo)
 *
 * Esas tres acciones se suman a las anteriores y el resultado sale solo de la
 * fórmula de StockJornada. La diferencia es que cada cambio deja un rastro
 * legible: la jornada muestra "inicial 50 + 10 reponer - 3 merma".
 */
class StockAjusteRequestWeb extends BaseRequest
{
    public function authorize(): bool
    {
        // Ajustan las porciones el administrador y la cocina.
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCocina() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accion' => ['required', Rule::in([StockJornada::ACCION_CARGAR, StockJornada::ACCION_REPONER, StockJornada::ACCION_MERMA])],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'fecha' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'motivo' => [
                'nullable',
                'string',
                'max:200',
                // Una merma sin motivo no sirve de nada: es lo primero que se
                // pregunta al ver que el stock no cuadra.
                Rule::requiredIf(fn () => $this->input('accion') === StockJornada::ACCION_MERMA),
            ],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'accion.required' => 'Elige qué ajuste quieres hacer: cargar, reponer o registrar merma.',
            'accion.in' => 'El ajuste debe ser una de las tres acciones disponibles.',
            'cantidad.required' => 'Indica la cantidad de porciones.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'cantidad.max' => 'Esa cantidad es demasiado grande para un solo ajuste.',
            'fecha.date_format' => 'La fecha no tiene el formato correcto.',
            'fecha.before_or_equal' => 'No se puede cargar stock para una fecha futura.',
            'motivo.required' => 'Indica el motivo de la merma.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'accion' => 'ajuste',
            'cantidad' => 'cantidad de porciones',
            'fecha' => 'fecha',
            'motivo' => 'motivo',
        ];
    }

    /** La fecha a la que aplica el ajuste; hoy si no se indicó ninguna. */
    public function fechaAjuste(): string
    {
        return $this->validated('fecha') ?: now()->toDateString();
    }
}