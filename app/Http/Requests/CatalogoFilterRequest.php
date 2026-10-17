<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use Illuminate\Validation\Rule;

//faltan cambios aqui para mas rato arreglar
class CatalogoFilterRequest extends BaseRequest
{
    public const ORDENES = ['recientes', 'precio_asc', 'precio_desc', 'nombre'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categoria' => ['nullable', 'integer', Rule::exists('categorias', 'id')],
            'buscar' => ['nullable', 'string', 'max:100'],
            'orden' => ['nullable', 'string', Rule::in(self::ORDENES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'buscar.max' => 'La búsqueda no puede superar los 100 caracteres.',
            'categoria.exists' => 'La categoría seleccionada no existe.',
            'orden.in' => 'El criterio de ordenamiento no es válido.',
        ];
    }

    public function orden(): string
    {
        $orden = (string) $this->input('orden', 'recientes');

        return in_array($orden, self::ORDENES, true) ? $orden : 'recientes';
    }

    //aquii colocar la seguriadd contra el xss
    public function termino(): string
    {
        $termino = $this->input('buscar');

        return is_string($termino) ? $termino : '';
    }
}
