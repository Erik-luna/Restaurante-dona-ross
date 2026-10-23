<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;
final class Reglas
{
    public const NOMBRE = ['string', 'min:3', 'max:150', 'regex:/^[\pL\s\.\'\-]+$/u'];
    public const TELEFONO = ['string', 'min:7', 'max:20', 'regex:/^[\pN\s\+\-\(\)]+$/u'];
    public const DIRECCION = ['string', 'min:5', 'max:255', 'regex:/^[^<>\x00-\x1F\x{2028}-\x{202F}\x{FEFF}]+$/u'];
    public const TEXTO_LARGO = ['string', 'max:2000', 'regex:/^[^<>\x00-\x08\x0B\x0C\x0E-\x1F\x{2028}-\x{202F}\x{FEFF}]*$/u'];
    public static function contrasena(bool $confirmada = true): array
    {
        $reglas = ['required', 'string', 'min:8', Password::defaults()];
        if ($confirmada) {
            $reglas[] = 'confirmed';
        }

        return $reglas;
    }
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