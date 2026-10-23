<?php

namespace App\Http\Requests;

use App\Support\Reglas;

class ClienteUpdateRequest extends ClienteStoreRequest
{
    public function rules(): array
    {
        $reglas = parent::rules();

        $reglas['password'] = ['nullable', ...Reglas::contrasena()];
        $reglas['activo'] = ['nullable', 'boolean'];

        return $reglas;
    }

    public function cambiaPassword(): bool
    {
        return filled($this->input('password'));
    }

    public function activo(): ?bool
    {
        if (! $this->has('activo')) {
            return null;
        }

        return $this->boolean('activo');
    }
}
