<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use App\Support\Texto;
use Illuminate\Validation\Rule;

/**
 * Alta de cliente (registro público).
 *
 * DECISIONES DE SEGURIDAD
 * ------------------------
 * 1. Lista blanca en el nombre (`Reglas::NOMBRE`). No se "limpia" el nombre
 *    quitando etiquetas: se RECHAZA. Un campo que acepta cualquier carácter es un
 *    campo del que hay que fiarse de que la salida esté escapada; uno que sólo
 *    admite letras, espacios y `. ' -` no puede representar un payload, ni
 *    siquiera con codificación alternativa.
 *
 * 2. `unique:users,email` sobre la columna real. Impide el registro duplicado y,
 *    de paso, cierra la escalada de privilegios: un atacante que intente
 *    re-registrarse con el correo de un administrador recibe un error de
 *    validación en vez de crear un segundo usuario con ese correo.
 *
 * 3. `role` NO aparece en la lista. No es un campo de entrada: el rol lo fija
 *    {@see \App\Models\User::registrarCliente()} en el servidor. Aceptarlo sería
 *    permitir que cualquiera se auto-asigne `admin`.
 *
 * 4. `Password::defaults()` exige longitud mínima y descarta las contraseñas
 *    triviales (12345678, password, qwerty). Un hash bcrypt con 12 rondas encarece
 *    el ataque offline por fuerza bruta si una base de datos llegara a filtrarse.
 */
class RegisterRequest extends BaseRequest
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
            'name' => ['required', ...Reglas::NOMBRE],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => Reglas::contrasena(),
            'telefono' => ['nullable', ...Reglas::TELEFONO],
            'direccion' => ['nullable', ...Reglas::DIRECCION],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * ¿El cliente ha pedido que este navegador se recuerde como de confianza?
     *
     * Es un opt-in explícito: una casilla sin marcar significa que NO se emite la
     * cookie de 30 días.
     */
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

    /**
     * Datos ya validados y con el correo normalizado, listos para persistir.
     *
     * @return array<string, mixed>
     */
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
