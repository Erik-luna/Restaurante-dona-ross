<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
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