<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use Illuminate\Foundation\Http\FormRequest;
abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }
    public function messages(): array
    {
        return array_merge(Reglas::mensajes(), $this->mensajesPropios());
    }
    protected function mensajesPropios(): array
    {
        return [];
    }
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
    protected function esActualizacion(): bool
    {
        return $this->routeIs('*.update', '*.update.post');
    }
    protected function casillaSiVino(string $clave, bool $porDefectoAlCrear): bool
    {
        if ($this->has($clave)) {
            return $this->boolean($clave);
        }

        return $this->esActualizacion() ? false : $porDefectoAlCrear;
    }
    public function perPage(string $campo = 'per_page', int $porDefecto = 15, int $maximo = 50): int
    {
        $solicitado = (int) $this->input($campo, $porDefecto);

        return max(1, min($solicitado, $maximo));
    }
}