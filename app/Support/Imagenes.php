<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Soporte para las imágenes del sistema: guardado, URL pública,
 * existencia y borrado sobre el disco configurado en config/imagenes.php,
 * manteniendo compatibilidad con las rutas viejas.
 */
class Imagenes
{
    /**
     * Nombre del disco configurado para las imágenes.
     */
    public static function disco(): string
    {
        return (string) config('imagenes.disk', 'public');
    }

    /**
     * Instancia del disco configurado para las imágenes.
     */
    public static function almacenamiento(): Filesystem
    {
        return Storage::disk(static::disco());
    }

    /**
     * Guarda la imagen en el disco configurado y devuelve su ruta relativa
     * (por ejemplo "platos/uuid.jpg").
     */
    public static function guardar(UploadedFile $imagen, string $carpeta = 'platos'): string
    {
        $nombre = Str::uuid()->toString().'.'.strtolower($imagen->extension());

        $imagen->storeAs($carpeta, $nombre, static::disco());

        return $carpeta.'/'.$nombre;
    }

    /**
     * URL pública de una ruta de imagen. Soporta las rutas viejas:
     * - "img/platos/..." servidas directamente desde el directorio público.
     * - "productos/..." y "promociones/..." guardadas en el disco public.
     * Además, "storage/..." se resuelve con el enlace público y las URL
     * absolutas se devuelven tal cual.
     */
    public static function url(?string $ruta): ?string
    {
        if ($ruta === null || $ruta === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $ruta)) {
            return $ruta;
        }

        $ruta = str_replace('\\', '/', ltrim($ruta, '/'));

        if (str_starts_with($ruta, 'public/')) {
            $ruta = substr($ruta, strlen('public/'));
        }

        if (str_starts_with($ruta, 'storage/')) {
            return asset($ruta);
        }

        if (str_starts_with($ruta, 'img/')) {
            return asset($ruta);
        }

        // Rutas del disco configurado: platos/..., productos/..., promociones/...
        return static::almacenamiento()->url($ruta);
    }

    /**
     * Indica si existe el archivo de una ruta de imagen, mirando el disco
     * configurado, el disco public y el directorio público (rutas viejas).
     */
    public static function existe(?string $ruta): bool
    {
        if ($ruta === null || $ruta === '') {
            return false;
        }

        if (preg_match('#^https?://#i', $ruta)) {
            return true;
        }

        $ruta = str_replace('\\', '/', ltrim($ruta, '/'));

        if (str_starts_with($ruta, 'public/')) {
            $ruta = substr($ruta, strlen('public/'));
        }

        if (str_starts_with($ruta, 'storage/')) {
            $ruta = substr($ruta, strlen('storage/'));
        }

        if (str_starts_with($ruta, 'img/')) {
            return is_file(public_path($ruta));
        }

        return static::almacenamiento()->exists($ruta)
            || Storage::disk('public')->exists($ruta);
    }

    /**
     * Elimina el archivo de una ruta de imagen, ya esté en el disco
     * configurado, en el disco public o en el directorio público.
     */
    public static function eliminar(?string $ruta): void
    {
        if ($ruta === null || $ruta === '' || preg_match('#^https?://#i', $ruta)) {
            return;
        }

        $ruta = str_replace('\\', '/', ltrim($ruta, '/'));

        if (str_starts_with($ruta, 'public/')) {
            $ruta = substr($ruta, strlen('public/'));
        }

        if (str_starts_with($ruta, 'storage/')) {
            $ruta = substr($ruta, strlen('storage/'));
        }

        if (str_starts_with($ruta, 'img/')) {
            $nombre = substr($ruta, strlen('img/'));

            if ($nombre !== '' && basename($nombre) === $nombre) {
                $absoluta = public_path('img/'.$nombre);

                if (is_file($absoluta) && ! unlink($absoluta)) {
                    throw new RuntimeException('No se pudo eliminar la imagen.');
                }
            }

            return;
        }

        // Solo carpetas planas con nombres de archivo simples.
        if (preg_match('#^[a-z0-9][a-z0-9\-]*/[A-Za-z0-9._\-]+$#', $ruta)) {
            static::almacenamiento()->delete($ruta);

            if (static::disco() !== 'public') {
                Storage::disk('public')->delete($ruta);
            }
        }
    }
}
