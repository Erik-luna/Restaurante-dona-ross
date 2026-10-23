<?php

namespace App\Http\Requests;

use App\Models\StockJornada;
use Illuminate\Validation\Rule;

class StockAjusteRequestWeb extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true
            || $this->user()?->esCocina() === true;
    }
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
    public function attributes(): array
    {
        return [
            'accion' => 'ajuste',
            'cantidad' => 'cantidad de porciones',
            'fecha' => 'fecha',
            'motivo' => 'motivo',
        ];
    }

    public function fechaAjuste(): string
    {
        return $this->validated('fecha') ?: now()->toDateString();
    }
}