<?php

namespace App\Http\Requests;

use App\Support\Texto;

class LoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'email.required' => 'Debes ingresar tu correo electrónico.',
            'email.email' => 'El formato del correo es inválido.',
            'password.required' => 'Debes ingresar tu contraseña.',
        ];
    }
    public function credenciales(): array
    {
        return [
            'email' => Texto::normalizarEmail($this->string('email')->toString()),
            'password' => (string) $this->input('password'),
        ];
    }

    public function recordar(): bool
    {
        return $this->boolean('remember');
    }
}
