<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContenidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'telefono' => ['nullable', 'string', 'max:40', 'regex:/^\+?[\d\s()\-]+$/'],
            'whatsapp' => ['nullable', 'string', 'max:40', 'regex:/^\+?[\d\s()\-]+$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'qr_pago' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'eliminar_qr_pago' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'telefono.regex' => 'El teléfono solo puede tener dígitos, espacios, guiones, paréntesis y el "+" inicial.',
            'whatsapp.regex' => 'El WhatsApp solo puede tener dígitos, espacios, guiones, paréntesis y el "+" inicial.',
            'facebook.url' => 'La dirección de Facebook debe empezar por http:// o https://',
            'instagram.url' => 'La dirección de Instagram debe empezar por http:// o https://',
            'qr_pago.image' => 'El archivo debe ser una imagen.',
            'qr_pago.mimes' => 'El QR debe ser una imagen en formato JPG, PNG o WebP.',
            'qr_pago.max' => 'La imagen del QR no puede pesar más de 2 MB.',
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
            'qr_pago' => 'código QR de pago',
        ];
    }
}