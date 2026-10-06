<?php

namespace App\Http\Requests;

use App\Support\Reglas;

/**
 * Edición de un cliente desde el panel web.
 *
 * Dos diferencias con `ClienteStoreRequest`:
 *
 *  - `password` pasa a opcional. Pedir una contraseña nueva en cada guardado de
 *    teléfono o dirección empujaría al personal a poner siempre la misma clave,
 *    o a inventar una y comunicársela al cliente por un canal inseguro.
 *
 *  - `activo` se valida como booleo estricto y se LEE de forma distinta. Antes se
 *    usaba `$request->boolean('activo', true)`: si el formulario no enviaba el
 *    campo (por ejemplo, una casilla sin marcar nunca aparece en el cuerpo),
 *    `boolean()` devolvía `true` y reactivaba silenciosamente una cuenta que el
 *    administrador acababa de desactivar. Ahora `activo()` devuelve `null` cuando
 *    el campo no viene, y el controlador solo escribe la columna en ese caso.
 */
class ClienteUpdateRequest extends ClienteStoreRequest
{
    /**
     * @return array<string, mixed>
     */
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
