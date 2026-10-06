<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Alta y edición de productos del panel de administración.
 */
class ProductoRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999.99'],
            // Las porciones son la carga del día de hoy, no un atributo del
            // producto: se guardan en stock_jornadas. Al crear es obligatoria para
            // que el plato nazca vendible; al editar es opcional a propósito, porque
            // corregir el stock del día en pleno servicio es tarea de la pantalla de
            // stock, no de la ficha del producto. Editar la ficha no debe pisar lo
            // que la cocina ya cargó por la mañana.
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
            'precio.min' => 'El precio no puede ser negativo.',
            'porciones_iniciales.required' => 'Indica cuántas porción(es) se preparan para hoy.',
            'porciones_iniciales.min' => 'Las porción(es) no pueden ser negativas.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'imagen.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no debe pesar más de 2 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
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

    /**
     * Cuántas porción(es) se cargan hoy con este alta o edición.
     *
     * Al crear nunca vale 0 porque el campo es obligatorio. Al editar puede venir 0
     * o no venir: quien lo dejó en blanco no está pidiendo tocar el stock, así que el
     * controlador abre la jornada con 0 y no la modifica.
     */
    public function porcionesIniciales(): int
    {
        return (int) ($this->validated('porciones_iniciales') ?? 0);
    }

    /**
     * Datos del producto listos para guardar.
     *
     * Lo que se guarda no es exactamente lo que vino en el formulario, por dos
     * motivos:
     *  - las casillas "destacado" y "activo" sólo llegan si el administrador las
     *    marcó, así que se resuelven con casillaSiVino();
     *  - la imagen se añade aparte, porque su nombre lo genera el servidor y no
     *    el navegador (ver ProductoController::store).
     *
     * `porciones_iniciales` NO va aquí: no es una columna de productos sino una
     * fila de stock_jornadas, y el controlador la abre aparte con StockService.
     *
     * @param  array<string, mixed>  $imagen  ['imagen' => ruta] si se subió un archivo.
     * @return array<string, mixed>
     */
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