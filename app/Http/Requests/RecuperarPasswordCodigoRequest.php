<?php

namespace App\Http\Requests;

use App\Support\Texto;
use Illuminate\Validation\Rules\Password;

class RecuperarPasswordCodigoRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }
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
