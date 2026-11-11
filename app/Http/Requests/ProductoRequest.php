<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductoRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true;
    }
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999.99'],
            'porciones_iniciales' => [
                'nullable',
                'integer',
                'min:0',
                'max:100000',
                Rule::requiredIf(fn () => ! $this->esActualizacion()),
            ],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'destacado' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'nombre.required' => 'Indica el nombre del producto.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'precio.required' => 'Indica el precio del producto.',
            'precio.decimal' => 'El precio debe ser un número con como máximo dos decimales.',
            'precio.gt' => 'El precio debe ser mayor que 0.',
            'precio.min' => 'El precio no puede ser negativo.',
            'porciones_iniciales.required' => 'Indica cuántas porción(es) se preparan para hoy.',
            'porciones_iniciales.min' => 'Las porción(es) no pueden ser negativas.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'imagen.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no debe pesar más de 2 MB.',
        ];
    }
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del producto',
            'descripcion' => 'descripción',
            'precio' => 'precio',
            'porciones_iniciales' => 'porciones de hoy',
            'categoria_id' => 'categoría',
            'imagen' => 'imagen',
        ];
    }
    public function porcionesIniciales(): int
    {
        return (int) ($this->validated('porciones_iniciales') ?? 0);
    }
    public function atributosProducto(array $imagen = []): array
    {
        return array_merge([
            'categoria_id' => $this->validated('categoria_id'),
            'nombre' => $this->validated('nombre'),
            'descripcion' => $this->validated('descripcion'),
            'precio' => $this->validated('precio'),
            'destacado' => $this->casillaSiVino('destacado', false),
            'activo' => $this->casillaSiVino('activo', true),
        ], $imagen);
    }
}