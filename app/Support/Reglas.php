<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Reglas de validación reutilizables y mensajes en español.
 *
 * Estas reglas se declaran aquí una sola vez y se usan en todos los formularios
 * (Form Requests) con la sintaxis  'nombre' => ['required', ...Reglas::NOMBRE],
 * para que todos los formularios del sistema acepten exactamente el mismo formato.
 */
final class Reglas
{
    /**
     * Nombre de persona: sólo letras, espacios, punto, apóstrofo y guion.
     * Ejemplos válidos: "María José", "O'Neill", "Pérez-Smith".
     */
    public const NOMBRE = ['string', 'min:3', 'max:150', 'regex:/^[\pL\s\.\'\-]+$/u'];

    /**
     * Teléfono: números y los separadores habituales.
     * Ejemplos válidos: "70000000", "+591 70000000", "(2) 2233445".
     */
    public const TELEFONO = ['string', 'min:7', 'max:20', 'regex:/^[\pN\s\+\-\(\)]+$/u'];

    /**
     * Dirección: texto libre acotado. No se restringen las letras porque una
     * dirección real puede contener casi cualquier cosa. Sólo se rechazan los signs
     * < y > y los caracteres de control, que no se escriben nunca a mano y sirven
     * para esconder código dentro del texto.
     */
    public const DIRECCION = ['string', 'min:5', 'max:255', 'regex:/^[^<>\x00-\x1F\x{2028}-\x{202F}\x{FEFF}]+$/u'];

    /**
     * Texto libre largo (notas, referencias). Igual que la dirección pero
     * admitiendo saltos de línea, que en una nota de pedido son legítimos.
     */
    public const TEXTO_LARGO = ['string', 'max:2000', 'regex:/^[^<>\x00-\x08\x0B\x0C\x0E-\x1F\x{2028}-\x{202F}\x{FEFF}]*$/u'];

    /**
     * Contraseña: mínimo 8 caracteres y que no sea trivial (1, 123456, password).
     * Con $confirmada = true además exige repetirla en el campo de confirmación.
     */
    public static function contrasena(bool $confirmada = true): array
    {
        $reglas = ['required', 'string', 'min:8', Password::defaults()];

        if ($confirmada) {
            $reglas[] = 'confirmed';
        }

        return $reglas;
    }

    /**
     * Mensajes de error en español para las reglas más usadas.
     *
     * @return array<string, string>
     */
    public static function mensajes(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'email' => 'El campo :attribute debe ser un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'min.string' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max.string' => 'El campo :attribute no puede superar los :max caracteres.',
            'numeric' => 'El campo :attribute debe ser un valor numérico.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'array' => 'El campo :attribute debe ser una lista.',
            'confirmed' => 'La confirmación de :attribute no coincide.',
            'current_password' => 'La contraseña actual ingresada es incorrecta.',
        ];
    }
}