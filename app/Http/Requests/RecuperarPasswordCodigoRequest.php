<?php

namespace App\Http\Requests;

use App\Support\Texto;
use Illuminate\Validation\Rules\Password;

/**
 * Paso 1 de la recuperación de contraseña: solicitud del código.
 *
 * El mensaje de respuesta NO debe distinguir entre "no existe ese correo" y
 * "existe pero está desactivado": hacerlo convierte el formulario en un oráculo
 * que permite enumerar las cuentas registradas del restaurante. El controlador
 * devuelve un mensaje genérico en ambos casos.
 */
class RecuperarPasswordCodigoRequest extends BaseRequest
{
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
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'email.required' => 'Debes introducir tu correo electrónico.',
            'email.email' => 'Introduce un formato de correo electrónico válido.',
        ];
    }

    public function correoNormalizado(): string
    {
        return Texto::normalizarEmail((string) $this->input('email'));
    }
}
