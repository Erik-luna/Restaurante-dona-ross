<?php

use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use App\Support\Imagenes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * P5: las imágenes deben guardarse en un disco configurable
 * (IMAGENES_DISK, por defecto "public") y las rutas viejas
 * (img/platos/, productos/, promociones/) deben seguir resolviendo.
 */

function p5Admin(): User
{
    return User::factory()->administrador()->create(['activo' => true]);
}

function p5ImagenValida(string $nombre = 'plato.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $nombre,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWZ8AAAAASUVORK5CYII=')
    );
}

function p5CrearProductoConImagen(\Tests\TestCase $test): Producto
{
    $test->actingAs(p5Admin())
        ->post(route('admin.productos.store'), [
            'nombre' => 'Plato con imagen',
            'descripcion' => 'Descripción de prueba',
            'precio' => 25,
            'porciones_iniciales' => 10,
            'imagen' => p5ImagenValida(),
        ])
        ->assertRedirect(route('admin.productos.index'));

    return Producto::where('nombre', 'Plato con imagen')->firstOrFail();
}

test('el panel guarda las imágenes de platos en el disco configurado', function () {
    Storage::fake('public');

    $producto = p5CrearProductoConImagen($this);

    expect($producto->imagen)->toStartWith('platos/')
        ->and(Storage::disk('public')->exists($producto->imagen))->toBeTrue()
        ->and($producto->imagen_url)->toBe(Storage::disk('public')->url($producto->imagen))
        ->and(is_file(public_path('img/platos/'.basename($producto->imagen))))->toBeFalse();
});

test('el disco de imágenes se cambia con la configuración imagenes.disk', function () {
    Storage::fake('imagenes-pruebas', ['url' => 'http://localhost/imagenes-pruebas']);
    config(['imagenes.disk' => 'imagenes-pruebas']);

    $producto = p5CrearProductoConImagen($this);

    expect($producto->imagen)->toStartWith('platos/')
        ->and(Storage::disk('imagenes-pruebas')->exists($producto->imagen))->toBeTrue()
        ->and(Storage::disk('public')->exists($producto->imagen))->toBeFalse()
        ->and($producto->imagen_url)->toBe('http://localhost/imagenes-pruebas/'.$producto->imagen);
});

test('las rutas viejas de imagen siguen resolviendo a sus URLs', function () {
    expect(Imagenes::url(null))->toBeNull()
        ->and(Imagenes::url('img/platos/plato1.jpeg'))->toBe(asset('img/platos/plato1.jpeg'))
        ->and(Imagenes::url('productos/vieja.jpg'))->toBe(Storage::disk('public')->url('productos/vieja.jpg'))
        ->and(Imagenes::url('promociones/vieja.jpg'))->toBe(Storage::disk('public')->url('promociones/vieja.jpg'))
        ->and(Imagenes::url('http://ejemplo.com/remota.jpg'))->toBe('http://ejemplo.com/remota.jpg');
});

test('actualizar la imagen reemplaza el archivo en el disco', function () {
    Storage::fake('public');

    $producto = p5CrearProductoConImagen($this);
    $rutaAnterior = $producto->imagen;

    $this->actingAs(p5Admin())
        ->put(route('admin.productos.update', $producto), [
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'precio' => $producto->precio,
            'imagen' => p5ImagenValida('segunda.png'),
        ])
        ->assertRedirect(route('admin.productos.index'));

    $producto->refresh();
    $rutaNueva = $producto->imagen;

    expect($rutaNueva)->not->toBe($rutaAnterior)
        ->and(Storage::disk('public')->exists($rutaNueva))->toBeTrue()
        ->and(Storage::disk('public')->exists($rutaAnterior))->toBeFalse()
        ->and($producto->imagen_url)->toBe(Storage::disk('public')->url($rutaNueva));
});

test('imagenes:migrar copia al disco configurado sin borrar los originales', function () {
    Storage::fake('public');
    Storage::fake('destino-migracion', ['url' => 'http://localhost/destino-migracion']);
    config(['imagenes.disk' => 'destino-migracion']);

    // Imagen vieja de producto servida desde el directorio público.
    $nombre = 'migrar-'.Str::uuid()->toString().'.jpg';
    File::ensureDirectoryExists(public_path('img/platos'));
    File::put(public_path('img/platos/'.$nombre), 'contenido-de-prueba');
    $producto = $this->crearProducto(5, ['imagen' => 'img/platos/'.$nombre]);

    // Promoción con imagen en el disco public de Laravel.
    Storage::disk('public')->put('promociones/vieja.jpg', 'promo-vieja');
    $promocion = Promocion::create([
        'titulo' => 'Promo de prueba',
        'descripcion' => 'Descripción',
        'descuento_porcentaje' => 10,
        'imagen' => 'promociones/vieja.jpg',
        'activo' => true,
    ]);

    try {
        $this->artisan('imagenes:migrar')->assertSuccessful();

        expect(Storage::disk('destino-migracion')->exists('platos/'.$nombre))->toBeTrue()
            ->and(Storage::disk('destino-migracion')->exists('promociones/vieja.jpg'))->toBeTrue()
            ->and(is_file(public_path('img/platos/'.$nombre)))->toBeTrue()
            ->and(Storage::disk('public')->exists('promociones/vieja.jpg'))->toBeTrue()
            ->and($producto->fresh()->imagen)->toBe('platos/'.$nombre)
            ->and($promocion->fresh()->imagen)->toBe('promociones/vieja.jpg');
    } finally {
        File::delete(public_path('img/platos/'.$nombre));
    }
});

test('imagenes:migrar con --dry-run no copia ni actualiza nada', function () {
    Storage::fake('public');
    Storage::fake('destino-migracion', ['url' => 'http://localhost/destino-migracion']);
    config(['imagenes.disk' => 'destino-migracion']);

    $nombre = 'migrar-'.Str::uuid()->toString().'.jpg';
    File::ensureDirectoryExists(public_path('img/platos'));
    File::put(public_path('img/platos/'.$nombre), 'contenido-de-prueba');
    $producto = $this->crearProducto(5, ['imagen' => 'img/platos/'.$nombre]);

    try {
        $this->artisan('imagenes:migrar', ['--dry-run' => true])->assertSuccessful();

        expect(Storage::disk('destino-migracion')->exists('platos/'.$nombre))->toBeFalse()
            ->and(is_file(public_path('img/platos/'.$nombre)))->toBeTrue()
            ->and($producto->fresh()->imagen)->toBe('img/platos/'.$nombre);
    } finally {
        File::delete(public_path('img/platos/'.$nombre));
    }
});
