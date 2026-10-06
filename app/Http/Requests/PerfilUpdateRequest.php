<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use Illuminate\Validation\Rule;

/**
 * Actualización del perfil del cliente (acción sensible — punto 3).
 *
 * REAUTENTICACIÓN OBLIGATORIA
 * ---------------------------
 * Si la petición incluye `password`, se exige `current_password`. La regla
 * `current_password` de Laravel no se limita a "comprobar que el campo viene":
 * verifica el hash contra la sesión actual y falla si no coincide.
 *
 * El motivo es concreto. Un atacante que consiga reutilizar la sesión de un
 * cliente (XSS, session fixation, dispositivo compartido en un navegador público)
 * podría cambiar la contraseña y adueñarse de la cuenta para siempre. Exigir la
 * contraseña actual —que el atacante no conoce porque la sesión no la contiene— es
 * lo que corta ese ataque.
 *
 * Y al cliente legítimo sólo le cuesta un paso, una vez, cuando realmente quiere
 * cambiar su clave. Ese es el balance buscado: fricción en la acción crítica,
 * ausencia de fricción en el resto de la navegación.
 */
class PerfilUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        // Sólo el propio titular. La ruta ya exige `auth` + `role:cliente`, y el
        // controlador opera siempre sobre auth()->user(), nunca sobre un ID de la
        // petición, de modo que no hay superficie IDOR en este endpoint.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reglas = [
            'name' => ['required', ...Reglas::NOMBRE],
            'telefono' => ['nullable', ...Reglas::TELEFONO],
            'direccion' => ['nullable', ...Reglas::DIRECCION],
        ];

        // La reautenticación sólo se exige si realmente se quiere cambiar la clave.
        if ($this->filled('password')) {
            $reglas['current_password'] = ['required', 'current_password'];
            $reglas['password'] = Reglas::contrasena();
        }

        return $reglas;
    }

    /**
     * ¿El cliente ha solicitado un cambio de contraseña en esta petición?
     */
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
