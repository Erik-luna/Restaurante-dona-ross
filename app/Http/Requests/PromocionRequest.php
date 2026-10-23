<?php

namespace App\Http\Requests;

class PromocionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true;
    }
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'titulo.required' => 'Indica el título de la promoción.',
            'descuento_porcentaje.max' => 'El descuento no puede superar el 100 %.',
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'imagen.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no debe pesar más de 2 MB.',
        ];
    }
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'descripcion' => 'descripción',
            'descuento_porcentaje' => 'porcentaje de descuento',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'imagen' => 'imagen',
        ];
    }
    public function atributosPromocion(array $imagen = []): array
    {
        return array_merge([
            'titulo' => $this->validated('titulo'),
            'descripcion' => $this->validated('descripcion'),
            'descuento_porcentaje' => $this->validated('descuento_porcentaje'),
            'fecha_inicio' => $this->validated('fecha_inicio'),
            'fecha_fin' => $this->validated('fecha_fin'),
            'activo' => $this->casillaSiVino('activo', true),
        ], $imagen);
    }
}