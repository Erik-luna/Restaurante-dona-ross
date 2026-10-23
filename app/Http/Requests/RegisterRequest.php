<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use App\Support\Texto;
use Illuminate\Validation\Rule;

class RegisterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => ['required', ...Reglas::NOMBRE],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => Reglas::contrasena(),
            'telefono' => ['nullable', ...Reglas::TELEFONO],
            'direccion' => ['nullable', ...Reglas::DIRECCION],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
    public function recordarDispositivo(): bool
    {
        return $this->boolean('remember');
    }

    protected function mensajesPropios(): array
    {
        return [
            'name.regex' => 'El nombre contiene caracteres especiales no permitidos.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'telefono.regex' => 'El número de teléfono tiene un formato no válido.',
        ];
    }
    public function datosCliente(): array
    {
        $validados = $this->validated();

        return [
            'name' => $validados['name'],
            'email' => Texto::normalizarEmail($validados['email']),
            'password' => $validados['password'],
            'telefono' => $validados['telefono'] ?? null,
            'direccion' => $validados['direccion'] ?? null,
        ];
    }
}
