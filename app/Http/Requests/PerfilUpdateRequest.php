<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use Illuminate\Validation\Rule;

class PerfilUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
    public function rules(): array
    {
        $reglas = [
            'name' => ['required', ...Reglas::NOMBRE],
            'telefono' => ['nullable', ...Reglas::TELEFONO],
            'direccion' => ['nullable', ...Reglas::DIRECCION],
        ];
        if ($this->filled('password')) {
            $reglas['current_password'] = ['required', 'current_password'];
            $reglas['password'] = Reglas::contrasena();
        }

        return $reglas;
    }
    public function cambiaPassword(): bool
    {
        return $this->filled('password');
    }

    protected function mensajesPropios(): array
    {
        return [
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'telefono.regex' => 'El número de teléfono tiene un formato inválido.',
            'current_password.required' => 'Para cambiar tu contraseña, debes ingresar tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual ingresada es incorrecta.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ];
    }
}
