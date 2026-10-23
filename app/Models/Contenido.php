<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contenido extends Model
{
    protected $fillable = [
        'clave',
        'valor',
    ];
    public const EDITABLES = [
        'telefono' => 'Teléfono',
        'whatsapp' => 'WhatsApp',
        'direccion' => 'Dirección',
        'facebook' => 'Facebook (URL)',
        'instagram' => 'Instagram (URL)',
        'correo' => 'Correo electrónico',
    ];
    public static function valorDe(string $clave, ?string $defecto = null): string
    {
        return (string) (static::where('clave', $clave)->value('valor') ?? $defecto ?? '');
    }
    public static function valores(array $claves): array
    {
        $todos = static::pluck('valor', 'clave');

        return array_combine(
            $claves,
            array_map(fn (string $clave) => (string) ($todos[$clave] ?? ''), $claves)
        );
    }
    public static function telefonoEnlazable(): string
    {
        return static::enlaceTelefonico(static::valorDe('telefono'));
    }
    public static function whatsappEnlazable(): string
    {
        $digitos = preg_replace('/\D+/', '', static::valorDe('whatsapp'));

        return $digitos === null || $digitos === '' ? '' : 'https://wa.me/'.$digitos;
    }
    public static function enlaceTelefonico(?string $telefono): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono);

        return $digitos === null || $digitos === '' ? '' : 'tel:+'.$digitos;
    }
}