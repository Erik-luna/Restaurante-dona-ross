<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use App\Support\Texto;
use Illuminate\Validation\Rule;

class ClienteStoreRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() === true;
    }

    public function rules(): array
    {
        $clienteId = $this->route('cliente')?->id ?? $this->route('id');

        return [
            'name' => ['required', ...Reglas::NOMBRE],
            'email' => [
                'required', 'string', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($clienteId),
            ],
            'password' => ['required', ...Reglas::contrasena()],
            'telefono' => ['nullable', ...Reglas::TELEFONO],
            'direccion' => ['nullable', ...Reglas::DIRECCION],
        ];
    }

    public function datosCliente(): array
    {
        return [
            'name' => $this->validated('name'),
            'email' => $this->correoNormalizado(),
            'password' => $this->validated('password'),
            'telefono' => $this->validated('telefono') ?: null,
            'direccion' => $this->validated('direccion') ?: null,
        ];
    }

    public function correoNormalizado(): string
    {
        return Texto::normalizarEmail((string) $this->input('email'));
    }

    protected function mensajesPropios(): array
    {
        return [
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'email.unique' => 'Ya existe una cuenta registrada con ese correo.',
            'telefono.regex' => 'El formato del teléfono es inválido.',
        ];
    }
}
