<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use Illuminate\Validation\Rule;

/**
 * Filtros del catálogo público (búsqueda, categoría y orden).
 *
 * El término de búsqueda acaba dentro de una cláusula LIKE, así que hay dos
 * controles: aquí se limita a 100 caracteres, y en el controlador se escapan los
 * comodines con Texto::likeSeguro(). Sin ese escapado, buscar "%" (que es un
 * término perfectamente válido) devolvería el catálogo entero.
 */
class CatalogoFilterRequest extends BaseRequest
{
    /** Valores admitidos en el parámetro de ordenamiento. */
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

    /**
     * Término de búsqueda ya validado, o cadena vacía si no se buscó.
     */
    public function termino(): string
    {
        $termino = $this->input('buscar');

        return is_string($termino) ? $termino : '';
    }
}
