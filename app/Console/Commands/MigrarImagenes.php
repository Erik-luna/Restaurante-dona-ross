<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\Promocion;
use App\Support\Imagenes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MigrarImagenes extends Command
{
    protected $signature = 'imagenes:migrar {--dry-run : Solo mostrar lo que se haría, sin copiar ni actualizar nada}';

    protected $description = 'Copia las imágenes existentes al disco configurado (IMAGENES_DISK) sin borrar los archivos originales';

    private bool $simulacion;

    private array $estadisticas;

    public function handle(): int
    {
        $this->simulacion = (bool) $this->option('dry-run');
        $this->estadisticas = ['copiadas' => 0, 'actualizadas' => 0, 'ya_estaban' => 0, 'faltantes' => 0];

        $disco = Imagenes::disco();

        if ($this->simulacion) {
            $this->info('Modo simulación (--dry-run): no se copia ni se actualiza nada.');
        }

        $this->info("Disco de imágenes configurado: {$disco}");

        if ($disco === 'public' && ! $this->enlacePublicoExiste()) {
            $this->warn('Falta el enlace "public/storage": ejecute "php artisan storage:link" para que las imágenes del disco public se puedan ver en el navegador.');
        }

        foreach (Producto::query()->whereNotNull('imagen')->orderBy('id')->get() as $producto) {
            $this->migrarProducto($producto);
        }

        foreach (Promocion::query()->whereNotNull('imagen')->orderBy('id')->get() as $promocion) {
            $this->migrarPromocion($promocion);
        }

        $this->resumen();

        return self::SUCCESS;
    }

    private function migrarProducto(Producto $producto): void
    {
        $ruta = $this->normalizar($producto->imagen);

        if ($ruta === null) {
            return;
        }

        // Formatos viejos: "img/platos/x.jpg", "platos/x.jpg" o "x.jpg"
        // apuntan al archivo físico public/img/platos/x.jpg.
        if (preg_match('#^img/platos/([A-Za-z0-9._\-]+)$#', $ruta, $coincide)
            || preg_match('#^platos/([A-Za-z0-9._\-]+)$#', $ruta, $coincide)
            || preg_match('#^([A-Za-z0-9._\-]+)$#', $ruta, $coincide)) {
            $destino = 'platos/'.$coincide[1];
        } else {
            // "productos/x.jpg" y demás rutas de disco se mantienen iguales.
            $destino = $ruta;
        }

        if ($this->copiarAlDisco($ruta, $destino, "producto {$producto->id}")
            && $destino !== $ruta
            && ! $this->simulacion) {
            $producto->forceFill(['imagen' => $destino])->save();
            $this->estadisticas['actualizadas']++;
        }
    }

    private function migrarPromocion(Promocion $promocion): void
    {
        $ruta = $this->normalizar($promocion->imagen);

        if ($ruta === null) {
            return;
        }

        // Las rutas de promoción ("promociones/x.jpg") son rutas de disco:
        // se copian tal cual y la fila no necesita cambios.
        $this->copiarAlDisco($ruta, $ruta, "promoción {$promocion->id}");
    }

    /**
     * Copia el archivo de una ruta de imagen al disco configurado.
     * Busca el original en el disco configurado, en el disco public y
     * en el directorio público (formatos viejos). Nunca borra el original.
     */
    private function copiarAlDisco(string $ruta, string $destino, string $etiqueta): bool
    {
        if (Imagenes::almacenamiento()->exists($destino)) {
            $this->estadisticas['ya_estaban']++;
            $this->line("[ya estaba] {$etiqueta}: {$destino}");

            return true;
        }

        $origenes = [
            public_path('img/'.$ruta), // platos/x.jpg → public/img/platos/x.jpg
            public_path($ruta),       // img/platos/x.jpg → public/img/platos/x.jpg
        ];

        foreach ($origenes as $origen) {
            if (is_file($origen)) {
                if (! $this->simulacion) {
                    Imagenes::almacenamiento()->put($destino, File::get($origen));
                }

                $this->estadisticas['copiadas']++;
                $this->info("[copiada] {$etiqueta}: {$ruta} → {$destino} (el original no se borra)");

                return true;
            }
        }

        if (Storage::disk('public')->exists($ruta)) {
            if (! $this->simulacion) {
                Imagenes::almacenamiento()->put($destino, Storage::disk('public')->get($ruta));
            }

            $this->estadisticas['copiadas']++;
            $this->info("[copiada] {$etiqueta}: {$ruta} → {$destino} (el original no se borra)");

            return true;
        }

        $this->estadisticas['faltantes']++;
        $this->warn("[sin archivo] {$etiqueta}: no se encontró el archivo de {$ruta}");

        return false;
    }

    private function normalizar(?string $imagen): ?string
    {
        $imagen = trim((string) $imagen);

        if ($imagen === '' || preg_match('#^https?://#i', $imagen)) {
            return null;
        }

        $imagen = str_replace('\\', '/', $imagen);

        if (str_starts_with($imagen, 'storage/')) {
            $imagen = substr($imagen, strlen('storage/'));
        }

        if (str_starts_with($imagen, 'public/')) {
            $imagen = substr($imagen, strlen('public/'));
        }

        return ltrim($imagen, '/');
    }

    private function enlacePublicoExiste(): bool
    {
        $enlace = public_path('storage');

        return is_link($enlace) || is_dir($enlace) || file_exists($enlace);
    }

    private function resumen(): void
    {
        $this->newLine();

        $mensaje = sprintf(
            'Listo: %d copiadas, %d ya estaban en el disco, %d rutas actualizadas, %d sin archivo.',
            $this->estadisticas['copiadas'],
            $this->estadisticas['ya_estaban'],
            $this->estadisticas['actualizadas'],
            $this->estadisticas['faltantes'],
        );

        if ($this->simulacion) {
            $this->info($mensaje.' (simulación: nada se copió ni se actualizó)');

            return;
        }

        $this->info($mensaje);

        if ($this->estadisticas['copiadas'] > 0) {
            $this->line('Los archivos originales se conservaron: puede borrarlos manualmente cuando confirme que todo se ve bien.');
        }
    }
}
