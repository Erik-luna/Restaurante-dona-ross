<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Clase base de todos los formularios del sistema.
 *
 * Aporta tres cosas a las subclases:
 *  - los mensajes de error en español, para que el usuario no vea textos de Laravel;
 *  - los nombres de los campos traducidos, para que el error diga "El campo nombre
 *    es obligatorio" y no "The name field is required";
 *  - dos ayudas para los formularios de edición (ver más abajo).
 */
abstract class BaseRequest extends FormRequest
{
    /**
     * Indica quién puede enviar este formulario.
     *
     * Por omisión devuelve false, así que un Form Request nuevo nace cerrado:
     * hay que decidir explícitamente quién puede usarlo. Sin esto es fácil crear
     * un formulario y olvidar esta comprobación.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(Reglas::mensajes(), $this->mensajesPropios());
    }

    /**
     * Mensajes específicos de esta subclase.
     *
     * @return array<string, string>
     */
    protected function mensajesPropios(): array
    {
        return [];
    }

    /**
     * Traduce los nombres de campo técnicos a los que ve el usuario.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'current_password' => 'contraseña actual',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'direccion_entrega' => 'dirección de entrega',
            'telefono_contacto' => 'teléfono de contacto',
            'notas' => 'notas',
            'cliente' => 'nombre',
            'pedido' => 'pedido',
            'buscar' => 'búsqueda',
            'categoria' => 'categoría',
            'codigo' => 'código de verificación',
            'cantidad' => 'cantidad',
            'items' => 'productos del pedido',
        ];
    }

    /**
     * ¿Este formulario está EDITANDO un registro que ya existe?
     *
     * Se deduce del nombre de la ruta, porque las de alta se llaman *.store y las
     * de edición *.update. Lo usan las dos funciones siguientes.
     */
    protected function esActualizacion(): bool
    {
        return $this->routeIs('*.update', '*.update.post');
    }

    /**
     * Lee una casilla de verificación ("checkbox") teniendo en cuenta que el
     * navegador sólo la envía si está marcada.
     *
     * Si el campo no viene en absoluto, se devuelve: al crear, el valor que el
     * negocio espera (un producto nuevo nace activo, el destacado nace apagado);
     * al editar, false, porque el formulario reemplaza el registro completo.
     *
     * Sin este detalle no se podría desactivar un producto desde el panel: al
     * desmarcar la casilla el navegador no manda el campo y el valor anterior se
     * mantendría.
     */
    protected function casillaSiVino(string $clave, bool $porDefectoAlCrear): bool
    {
        if ($this->has($clave)) {
            return $this->boolean($clave);
        }

        return $this->esActualizacion() ? false : $porDefectoAlCrear;
    }

    /**
     * Cantidad de registros por página, con un máximo.
     *
     * Sin ese techo, alguien podría pedir 100000 registros y dejar sin memoria al
     * servidor con una sola URL.
     */
    public function perPage(string $campo = 'per_page', int $porDefecto = 15, int $maximo = 50): int
    {
        $solicitado = (int) $this->input($campo, $porDefecto);

        return max(1, min($solicitado, $maximo));
    }
}