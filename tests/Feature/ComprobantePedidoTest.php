<?php

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('el administrador puede ver el comprobante y el enlace de retorno carga con HTTP 200 sin error 500', function () {
    $admin = User::where('role', User::ROL_ADMINISTRADOR)->first();
    $cliente = User::where('role', User::ROL_CLIENTE)->first();
    $producto = Producto::where('activo', true)->first();

    $pedido = Pedido::create([
        'user_id' => $cliente->id,
        'estado' => Pedido::ESTADO_CONFIRMADO,
        'total' => $producto->precio,
        'direccion_entrega' => 'Av. Principal #123',
        'telefono_contacto' => '70000003',
        'metodo_pago' => 'efectivo',
    ]);

    // 1. Acceder al comprobante como administrador
    $comprobanteResponse = $this->actingAs($admin)
        ->get(route('pedidos.comprobante', $pedido))
        ->assertOk();

    // Debe contener el enlace al detalle del pedido del panel de administración
    $rutaRetornoEsperada = route('admin.pedidos.show', $pedido);
    $comprobanteResponse->assertSee($rutaRetornoEsperada);

    // 2. El enlace de retorno debe cargar con éxito con HTTP 200
    $retornoResponse = $this->actingAs($admin)
        ->get($rutaRetornoEsperada)
        ->assertOk();

    $retornoResponse->assertSee('Pedido #' . $pedido->id);
});

test('el cajero puede ver el comprobante y retornar al detalle de pedido correctamente', function () {
    $cajero = User::where('role', User::ROL_CAJERO)->first();
    $cliente = User::where('role', User::ROL_CLIENTE)->first();
    $producto = Producto::where('activo', true)->first();

    $pedido = Pedido::create([
        'user_id' => $cliente->id,
        'estado' => Pedido::ESTADO_CONFIRMADO,
        'total' => $producto->precio,
        'direccion_entrega' => 'Av. Principal #123',
        'telefono_contacto' => '70000003',
        'metodo_pago' => 'qr',
    ]);

    $comprobanteResponse = $this->actingAs($cajero)
        ->get(route('pedidos.comprobante', $pedido))
        ->assertOk();

    $rutaRetornoEsperada = route('cajero.pedidos.show', $pedido);
    $comprobanteResponse->assertSee($rutaRetornoEsperada);

    $this->actingAs($cajero)
        ->get($rutaRetornoEsperada)
        ->assertOk();
});

test('el cliente puede ver su comprobante y retornar al detalle de su pedido correctamente', function () {
    $cliente = User::where('role', User::ROL_CLIENTE)->first();
    $producto = Producto::where('activo', true)->first();

    $pedido = Pedido::create([
        'user_id' => $cliente->id,
        'estado' => Pedido::ESTADO_CONFIRMADO,
        'total' => $producto->precio,
        'direccion_entrega' => 'Av. Principal #123',
        'telefono_contacto' => '70000003',
        'metodo_pago' => 'efectivo',
    ]);

    $comprobanteResponse = $this->actingAs($cliente)
        ->get(route('pedidos.comprobante', $pedido))
        ->assertOk();

    $rutaRetornoEsperada = route('cliente.pedidos.show', $pedido);
    $comprobanteResponse->assertSee($rutaRetornoEsperada);

    $this->actingAs($cliente)
        ->get($rutaRetornoEsperada)
        ->assertOk();
});
