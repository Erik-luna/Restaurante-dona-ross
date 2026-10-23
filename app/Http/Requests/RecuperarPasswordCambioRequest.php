<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use App\Support\Texto;

class RecuperarPasswordCambioRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'codigo' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'password' => Reglas::contrasena(),
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'email.required' => 'Debes introducir tu correo.',
            'codigo.required' => 'Debes introducir el código de verificación.',
            'codigo.size' => 'El código debe tener exactamente 6 dígitos.',
            'codigo.regex' => 'El código únicamente contiene números.',
            'password.required' => 'Debes introducir la nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function correoNormalizado(): string
    {
        return Texto::normalizarEmail((string) $this->input('email'));
    }

    public function codigo(): string
    {
        return (string) $this->input('codigo');
    }
}
