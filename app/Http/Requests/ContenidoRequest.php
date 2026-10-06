<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de los datos de contacto del sitio.
 *
 * La regla que de verdad importa no es de longitud: es que **el teléfono solo
 * contenga caracteres de teléfono**. Sin ella se guardaría "llamar al mostrador" en
 * el campo del teléfono, y el enlace `tel:` del pie quedaría apuntando a un número
 * que no existe sin que nadie se entere hasta que un cliente no pueda llamar.
 *
 * Se acepta el formato con el que la gente anota los números ("+591 (2) 700-0000")
 * y se limpian al generar el enlace, porque un `tel:+591 (2) 700-0000` no funciona.
 */
class ContenidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            // Se permiten '+', espacios, guiones y paréntesis: así se puede escribir
            // "+591 (2) 700-0000" como lo tiene la gente anotado. Lo que se exige son
            // dígitos, para que el número exista.
            'telefono' => ['nullable', 'string', 'max:40', 'regex:/^\+?[\d\s()\-]+$/'],
            'whatsapp' => ['nullable', 'string', 'max:40', 'regex:/^\+?[\d\s()\-]+$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'telefono.regex' => 'El teléfono solo puede tener dígitos, espacios, guiones, paréntesis y el "+" inicial.',
            'whatsapp.regex' => 'El WhatsApp solo puede tener dígitos, espacios, guiones, paréntesis y el "+" inicial.',
            'facebook.url' => 'La dirección de Facebook debe empezar por http:// o https://',
            'instagram.url' => 'La dirección de Instagram debe empezar por http:// o https://',
        ];
    }

    public function attributes(): array
    {
        return [
            'telefono' => 'teléfono',
            'whatsapp' => 'WhatsApp',
            'direccion' => 'dirección',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'correo' => 'correo',
        ];
    }
}