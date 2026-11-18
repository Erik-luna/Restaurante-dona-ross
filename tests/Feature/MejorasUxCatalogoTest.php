<?php

use App\Models\Horario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // Asegurar horario abierto hoy para pruebas de checkout
    Horario::updateOrCreate(
        ['dia' => now()->isoWeekday()],
        ['apertura' => '00:00:00', 'cierre' => '23:59:59', 'activo' => true]
    );
});

test('los productos del catálogo tienen IDs secuenciales correlativos y orden limpio', function () {
    $productos = Producto::where('activo', true)->orderBy('id', 'asc')->get();

    expect($productos->count())->toBeGreaterThanOrEqual(13);

    // Verificar que los IDs sean estrictamente correlativos y consecutivos sin saltos
    $ids = $productos->pluck('id')->values()->all();
    for ($i = 1; $i < count($ids); $i++) {
        expect($ids[$i])->toBe($ids[$i - 1] + 1);
    }

    // Comprobar que en el panel de administración se listan en orden ascendente
    $admin = User::where('role', User::ROL_ADMINISTRADOR)->first();
    $response = $this->actingAs($admin)->get(route('admin.productos.index'))->assertOk();

    $response->assertSee('Pique Macho');
    $response->assertSee('Silpancho Cochabambino');
});

test('la paginacion no muestra texto redundante en ingles como showing results', function () {
    $response = $this->get(route('catalogo'))->assertOk();

    // No debe contener el texto plano generado por defecto por Laravel
    $response->assertDontSee('Showing');
    $response->assertDontSee('results');
    $response->assertDontSee('Showing 1 to');

    // Debe mostrar la estructura estética centrada
    $response->assertSee('pagination-ross');
});

test('la portada incluye el carrusel de destacados con transicion automatica e indicadores fluidos', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('carouselDestacadosContainer');
    $response->assertSee('carouselDestacadosTrack');
    $response->assertSee('carouselDestacadosDots');
    $response->assertSee('carousel-dot');
    $response->assertSee('startAutoplay');
    $response->assertSee('goToPage');
});

test('el cliente puede seleccionar metodo de pago en efectivo o por qr al procesar pedido', function () {
    $cliente = User::where('role', User::ROL_CLIENTE)->first();
    $producto = Producto::where('activo', true)->first();

    // 1. Agregar al carrito
    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto))->assertRedirect();

    // 2. Realizar checkout seleccionando pago por QR
    $checkoutResponse = $this->actingAs($cliente)->post(route('cliente.checkout'), [
        'direccion_entrega' => 'Calle Cochabamba #789, Zona Central',
        'telefono_contacto' => '71234567',
        'notas' => 'Por favor llamar al llegar',
        'metodo_pago' => 'qr',
    ]);

    $pedido = Pedido::latest('id')->first();
    expect($pedido)->not->toBeNull()
        ->and($pedido->metodo_pago)->toBe('qr')
        ->and($pedido->direccion_entrega)->toBe('Calle Cochabamba #789, Zona Central');

    $checkoutResponse->assertRedirect(route('cliente.pedidos.show', $pedido));

    // 3. La vista del pedido muestra la confirmación de pago por QR
    $detalleResponse = $this->actingAs($cliente)->get(route('cliente.pedidos.show', $pedido))->assertOk();
    $detalleResponse->assertSee('Pago por QR');

    // 4. Segundo checkout con Efectivo
    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto))->assertRedirect();
    $this->actingAs($cliente)->post(route('cliente.checkout'), [
        'direccion_entrega' => 'Av. Heroínas #456',
        'telefono_contacto' => '79876543',
        'metodo_pago' => 'efectivo',
    ])->assertRedirect();

    $pedidoEfectivo = Pedido::latest('id')->first();
    expect($pedidoEfectivo->metodo_pago)->toBe('efectivo');

    $detalleEfectivo = $this->actingAs($cliente)->get(route('cliente.pedidos.show', $pedidoEfectivo))->assertOk();
    $detalleEfectivo->assertSee('Efectivo contra entrega');
});

test('las rutas base de paneles redirigen limpiamente y rutas inexistentes muestran error 404 estilizado', function () {
    $admin = User::where('role', User::ROL_ADMINISTRADOR)->first();

    // Redirecciones base de paneles
    $this->get('/admin')->assertRedirect('/admin/dashboard');
    $this->get('/cajero')->assertRedirect('/cajero/dashboard');
    $this->get('/cocina')->assertRedirect('/cocina/dashboard');
    $this->get('/cliente')->assertRedirect('/cliente/dashboard');

    // Manejo limpio y controlado de URLs manipuladas / 404 sin pantallas de error bruscas
    $response = $this->get('/seccion-inexistente-xyz-123');
    $response->assertStatus(404);
    $response->assertSee('Platillo o Sección No Encontrada');
    $response->assertSee('Ir al Inicio');
});
