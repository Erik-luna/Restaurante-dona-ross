<?php

namespace App\Http\Requests;

use App\Support\Reglas;
use App\Support\Texto;

/**
 * Paso 2 de la recuperación de contraseña: canje del código.
 *
 * `size:6` y la expresión regular `^[0-9]{6}$` son una lista blanca: sólo se
 * aceptan exactamente seis dígitos. Un `\d` en PCRE coincide con SÓLO dígitos
 * ASCII, de modo que no hay ambigüedad con cifras de otros alfabetos.
 *
 * El número de intentos restantes NO se controla aquí sino en el controlador,
 * porque depende del estado de la sesión (de cuántos intentos lleva el usuario con
 * ESTE código), que un Form Request no debería mutar.
 */
class RecuperarPasswordCambioRequest extends BaseRequest
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
