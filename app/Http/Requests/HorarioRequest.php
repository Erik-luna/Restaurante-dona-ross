<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validación de los horarios de atención.
 *
 * Hace falta un FormRequest y no un `validate()` en el controlador por una regla que
 * no se puede expresar con campos sueltos: **no se admite el mismo día dos veces**.
 * Sin esa comprobación, mandar el formulario con un día duplicado dejaría dos filas
 * para el mismo día y `Horario::deHoy()` devolvería siempre la primera, así que el
 * horario que el administrador acaba de corregir no se vería en la portada.
 *
 * No se valida que el cierre sea posterior a la apertura porque hay negocios que
 * cierran después de medianoche. El modelo interpreta un cierre menor que la apertura
 * como un turno que cruza el día, y `estaAbierto()` lo tiene en cuenta.
 */
class HorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Los horarios cambian la disponibilidad real del restaurante, así que los
        // cambia quien administra, no solo quien puede verlos.
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'dias' => ['required', 'array', 'min:1'],
            'dias.*.dia' => ['required', 'integer', 'between:1,7'],
            'dias.*.apertura' => ['required', 'date_format:H:i'],
            'dias.*.cierre' => ['required', 'date_format:H:i'],
            'dias.*.activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'dias.required' => 'Hay que enviar al menos un día de horario.',
            'dias.*.apertura.date_format' => 'La hora de apertura no es válida.',
            'dias.*.cierre.date_format' => 'La hora de cierre no es válida.',
            'dias.*.dia.between' => 'El día debe estar entre 1 (lunes) y 7 (domingo).',
        ];
    }

    /** Un día no puede aparecer dos veces en el mismo envío. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $dias = array_column($this->input('dias', []), 'dia');
            $repetidos = array_diff_assoc($dias, array_unique($dias));

            if ($repetidos !== []) {
                $validator->errors()->add('dias', 'No se puede repetir un día en el horario.');
            }
        });
    }
}