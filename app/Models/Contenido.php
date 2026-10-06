<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un dato editable del sitio, guardado como clave y valor.
 *
 * Los valores se piden una sola vez por petición, desde el middleware
 * `CompartirContenido`, que los comparte con todas las vistas. Por eso este modelo no
 * mantiene un caché estático: esa variable sobrevive entre peticiones, así que en las
 * pruebas un teléfono guardado en un test se colaría en el siguiente, y en un worker
 * de cola un cambio del administrador no se vería nunca.
 */
class Contenido extends Model
{
    protected $fillable = [
        'clave',
        'valor',
    ];

    /** Datos que el administrador puede cambiar desde la pantalla de contenido. */
    public const EDITABLES = [
        'telefono' => 'Teléfono',
        'whatsapp' => 'WhatsApp',
        'direccion' => 'Dirección',
        'facebook' => 'Facebook (URL)',
        'instagram' => 'Instagram (URL)',
        'correo' => 'Correo electrónico',
    ];

    /**
     * El valor de una clave, o el valor por defecto si la fila no existe.
     *
     * Si la fila existe pero está vacía se devuelve el vacío: es un dato que alguien
     * borró a propósito, y volver a rellenarlo con el valor anterior sería ignorar
     * esa decisión. El defecto solo cubre "nunca se creó la fila", que es lo que
     * pasa en una instalación recién migrada a la que no se le ha dado de alta.
     */
    public static function valorDe(string $clave, ?string $defecto = null): string
    {
        return (string) (static::where('clave', $clave)->value('valor') ?? $defecto ?? '');
    }

    /** Los valores de varias claves en una sola consulta, indexados por clave. */
    public static function valores(array $claves): array
    {
        $todos = static::pluck('valor', 'clave');

        return array_combine(
            $claves,
            array_map(fn (string $clave) => (string) ($todos[$clave] ?? ''), $claves)
        );
    }

    /**
     * Teléfono en formato de enlace, "tel:+59170000000".
     *
     * Si el teléfono se guarda como "+591 70000000" y se usa tal cual en el `href`,
     * el enlace no funciona: los espacios rompen el URI. Se usan solo los dígitos,
     * para que un número escrito con espacios o guiones siga sirviendo.
     */
    public static function telefonoEnlazable(): string
    {
        return static::enlaceTelefonico(static::valorDe('telefono'));
    }

    /** WhatsApp espera solo dígitos con el código de país, sin el "+". */
    public static function whatsappEnlazable(): string
    {
        $digitos = preg_replace('/\D+/', '', static::valorDe('whatsapp'));

        return $digitos === null || $digitos === '' ? '' : 'https://wa.me/'.$digitos;
    }

    /** Construye el `href` de un teléfono, o vacío si no hay número. */
    public static function enlaceTelefonico(?string $telefono): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono);

        return $digitos === null || $digitos === '' ? '' : 'tel:+'.$digitos;
    }
}