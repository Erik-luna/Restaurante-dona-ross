<?php

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

/*
 * P4: al cancelar un pedido de delivery las porciones deben volver a la
 * jornada de stock del día en que se hizo el pedido (donde se descontaron).
 */

function p4Cocina(): User
{
    return User::factory()->cocina()->create(['activo' => true]);
}

function p4Cliente(): User
{
    return User::factory()->create(['role' => User::ROL_CLIENTE, 'activo' => true]);
}

function p4Pedido(User $cliente, Producto $producto, int $cantidad = 3): Pedido
{
    $pedido = Pedido::create([
        'user_id' => $cliente->id,
        'estado' => Pedido::ESTADO_CONFIRMADO,
        'total' => (float) $producto->precio * $cantidad,
        'direccion_entrega' => 'Calle Falsa 123',
        'telefono_contacto' => '70000000',
    ]);

    PedidoItem::create([
        'pedido_id' => $pedido->id,
        'producto_id' => $producto->id,
        'cantidad' => $cantidad,
        'precio_unitario' => $producto->precio,
        'subtotal' => (float) $producto->precio * $cantidad,
    ]);

    // El mismo descuento que hace el checkout al confirmar el pedido.
    (new StockService())->descontar([['producto' => $producto, 'cantidad' => $cantidad]]);

    return $pedido;
}

test('CP-12: cancelar un pedido devuelve las porciones a la jornada', function () {
    $producto = $this->crearProducto(10);
    $pedido = p4Pedido(p4Cliente(), $producto, 3);

    expect($producto->disponibleEn())->toBe(7);

    $this->actingAs(p4Cocina())
        ->patch(route('cocina.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CANCELADO])
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CANCELADO)
        ->and($producto->disponibleEn())->toBe(10);
});

test('la devolución usa la jornada del día del pedido y no toca la de hoy', function () {
    $producto = $this->crearProducto(20);
    $ayer = now()->subDay()->toDateString();
    $this->cargarStock($producto, 15, $ayer);

    $cliente = p4Cliente();
    $pedido = Pedido::create([
        'user_id' => $cliente->id,
        'estado' => Pedido::ESTADO_CONFIRMADO,
        'total' => 100.0,
        'direccion_entrega' => 'Calle Falsa 123',
        'telefono_contacto' => '70000000',
    ]);
    PedidoItem::create([
        'pedido_id' => $pedido->id,
        'producto_id' => $producto->id,
        'cantidad' => 4,
        'precio_unitario' => $producto->precio,
        'subtotal' => 100.0,
    ]);

    // El pedido se hizo ayer: su stock se descontó de la jornada de ayer.
    $pedido->forceFill(['created_at' => now()->subDay()])->save();
    StockJornada::de($producto->id, $ayer)->consumir(4);

    expect($producto->disponibleEn($ayer))->toBe(11)
        ->and($producto->disponibleEn())->toBe(20);

    $this->actingAs(p4Cocina())
        ->patch(route('cocina.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CANCELADO])
        ->assertRedirect();

    expect($producto->disponibleEn($ayer))->toBe(15)
        ->and($producto->disponibleEn())->toBe(20);
});

test('volver a enviar cancelado no devuelve el stock dos veces', function () {
    $producto = $this->crearProducto(10);
    $pedido = p4Pedido(p4Cliente(), $producto, 3);

    $cocina = $this->actingAs(p4Cocina());

    $cocina->patch(route('cocina.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CANCELADO])
        ->assertRedirect();

    expect($producto->disponibleEn())->toBe(10);

    $cocina->patch(route('cocina.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CANCELADO])
        ->assertRedirect();

    expect($producto->disponibleEn())->toBe(10);
});

test('sin jornada de stock el pedido se cancela igual y queda el aviso en el log', function () {
    $producto = $this->crearProducto(10);
    $pedido = p4Pedido(p4Cliente(), $producto, 3);

    StockJornada::where('producto_id', $producto->id)->delete();

    Log::spy();

    $this->actingAs(p4Cocina())
        ->patch(route('cocina.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CANCELADO])
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CANCELADO)
        ->and(StockJornada::where('producto_id', $producto->id)->count())->toBe(0);

    Log::shouldHaveReceived('warning')->once();
});

test('la devolución no deja consumidos negativos', function () {
    $producto = $this->crearProducto(5);
    $jornada = StockJornada::de($producto->id);
    $jornada->forceFill(['consumidos' => 2])->save();

    (new StockService())->devolver([['producto' => $producto, 'cantidad' => 6]]);

    expect($jornada->fresh()->consumidos)->toBe(0)
        ->and($producto->disponibleEn())->toBe(5);
});

test('cambiar a un estado que no es cancelado no toca el stock', function () {
    $producto = $this->crearProducto(10);
    $pedido = p4Pedido(p4Cliente(), $producto, 3);

    $this->actingAs(p4Cocina())
        ->patch(route('cocina.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_PREPARANDO])
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_PREPARANDO)
        ->and($producto->disponibleEn())->toBe(7);
});

test('un pedido ya cobrado también puede cancelarse y devuelve el stock', function () {
    // Comportamiento reportado en P4: hoy la transición a "cancelado" no
    // considera si el pedido ya fue cobrado. No se bloquea por decisión de
    // esta corrección; solo se documenta en la auditoría.
    $producto = $this->crearProducto(10);
    $pedido = p4Pedido(p4Cliente(), $producto, 3);
    $pedido->forceFill(['estado' => Pedido::ESTADO_ENVIADO, 'pagado_at' => now()])->save();

    $this->actingAs(p4Cocina())
        ->patch(route('cocina.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CANCELADO])
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CANCELADO)
        ->and($producto->disponibleEn())->toBe(10);
});
