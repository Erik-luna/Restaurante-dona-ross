<?php

use App\Models\Categoria;
use App\Models\Producto;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('el seeder registra exactamente las 3 categorías requeridas y activas', function () {
    $this->artisan('db:seed')->assertSuccessful();

    $categoriasActivas = Categoria::activas()->pluck('nombre')->all();

    expect($categoriasActivas)->toHaveCount(3)
        ->and($categoriasActivas)->toContain('Platos principales')
        ->and($categoriasActivas)->toContain('Bebidas')
        ->and($categoriasActivas)->toContain('Postres');
});

test('el seeder puebla el catálogo con los platos típicos bolivianos y precios oficiales', function () {
    $this->artisan('db:seed')->assertSuccessful();

    $platosEsperados = [
        'Pique Macho' => 45.00,
        'Silpancho Cochabambino' => 35.00,
        'Fricasé Paceño' => 38.00,
        'Saice Tarijeño' => 32.00,
        'Chicharrón de Cerdo' => 40.00,
        'Ají de Llisa' => 28.00,
        'Mocochinchi tradicional' => 7.00,
        'Api con pastel' => 10.00,
        'Somó' => 6.00,
        'Refresco de Linaza' => 5.00,
        'Helado de Canela artesanal' => 8.00,
        'Dulce de Cayote con Queso' => 10.00,
        'Tawa-Tawas' => 6.00,
    ];

    foreach ($platosEsperados as $nombre => $precio) {
        $producto = Producto::where('nombre', $nombre)->first();

        expect($producto)->not->toBeNull("El producto {$nombre} debe existir en base de datos")
            ->and((float) $producto->precio)->toBe((float) $precio)
            ->and($producto->activo)->toBeTrue()
            ->and($producto->imagen)->not->toBeNull();
    }

    // Los 6 platos principales deben estar destacados
    $principales = [
        'Pique Macho',
        'Silpancho Cochabambino',
        'Fricasé Paceño',
        'Saice Tarijeño',
        'Chicharrón de Cerdo',
        'Ají de Llisa',
    ];

    foreach ($principales as $nombre) {
        $p = Producto::where('nombre', $nombre)->first();
        expect($p->destacado)->toBeTrue("El plato principal {$nombre} debe ser destacado");
    }
});

test('la portada muestra el carrusel interactivo con todos los platos destacados', function () {
    $this->artisan('db:seed')->assertSuccessful();

    $response = $this->get(route('home'))->assertOk();

    // Estructura interactiva del carrusel
    $response->assertSee('carouselDestacadosContainer');
    $response->assertSee('carouselDestacadosTrack');
    $response->assertSee('carouselDestacadosPrev');
    $response->assertSee('carouselDestacadosNext');
    $response->assertSee('carouselDestacadosDots');
    $response->assertSee('carousel-ross-item');

    // Deben aparecer los 6 platos destacados dentro del carrusel
    $response->assertSee('Pique Macho');
    $response->assertSee('Silpancho Cochabambino');
    $response->assertSee('Fricasé Paceño');
    $response->assertSee('Saice Tarijeño');
    $response->assertSee('Chicharrón de Cerdo');
    $response->assertSee('Ají de Llisa');

    // Los precios en Bs. se visualizan
    $response->assertSee('Bs. 45.00');
    $response->assertSee('Bs. 35.00');
    $response->assertSee('Bs. 38.00');
});

test('el catalogo permite filtrar por las 3 categorías requeridas', function () {
    $this->artisan('db:seed')->assertSuccessful();

    $catPrincipales = Categoria::where('nombre', 'Platos principales')->first();
    $catBebidas = Categoria::where('nombre', 'Bebidas')->first();
    $catPostres = Categoria::where('nombre', 'Postres')->first();

    // Filtro Platos principales
    $this->get(route('catalogo', ['categoria' => $catPrincipales->id]))
        ->assertOk()
        ->assertSee('Pique Macho')
        ->assertDontSee('Mocochinchi tradicional')
        ->assertDontSee('Tawa-Tawas');

    // Filtro Bebidas
    $this->get(route('catalogo', ['categoria' => $catBebidas->id]))
        ->assertOk()
        ->assertSee('Mocochinchi tradicional')
        ->assertDontSee('Pique Macho')
        ->assertDontSee('Tawa-Tawas');

    // Filtro Postres
    $this->get(route('catalogo', ['categoria' => $catPostres->id]))
        ->assertOk()
        ->assertSee('Tawa-Tawas')
        ->assertDontSee('Pique Macho')
        ->assertDontSee('Mocochinchi tradicional');
});
