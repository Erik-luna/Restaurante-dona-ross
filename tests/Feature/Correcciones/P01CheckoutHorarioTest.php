<?php

use App\Models\Horario;
use App\Models\Pedido;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * P1: el checkout debe bloquearse cuando el restaurante está cerrado.
 * Cobertura CP-10 / CP-11: límites de apertura/cierre, cruce de medianoche
 * y día sin horario configurado.
 */

function p1Cliente(): User
{
    return User::factory()->create(['role' => User::ROL_CLIENTE, 'activo' => true]);
}

function p1HorarioDeHoy(string $apertura, string $cierre, bool $activo = true): void
{
    Horario::updateOrCreate(
        ['dia' => now()->isoWeekday()],
        ['apertura' => $apertura, 'cierre' => $cierre, 'activo' => $activo]
    );
}

function p1DatosCheckout(): array
{
    return [
        'direccion_entrega' => 'Calle Falsa 123',
        'telefono_contacto' => '70000000',
    ];
}

afterEach(function () {
    Carbon::setTestNow();
});

test('CP-10: el checkout se rechaza un minuto antes de la apertura', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 07:59:00')); // miércoles, horario 08:00-20:00
    p1HorarioDeHoy('08:00:00', '20:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.carrito'))
        ->assertSessionHas('error', 'El restaurante está cerrado. Abrimos de 08:00 a 20:00.');

    expect(Pedido::count())->toBe(0)
        ->and($producto->disponibleEn())->toBe(10)
        ->and(session('carrito'))->toBe([$producto->id => 1]);
});

test('CP-10: el checkout se permite en la apertura exacta', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 08:00:00'));
    p1HorarioDeHoy('08:00:00', '20:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.pedidos'));

    expect(Pedido::count())->toBe(1)
        ->and($producto->disponibleEn())->toBe(9);
});

test('CP-10: el checkout se permite un minuto antes del cierre', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 19:59:00'));
    p1HorarioDeHoy('08:00:00', '20:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.pedidos'));

    expect(Pedido::count())->toBe(1)
        ->and($producto->disponibleEn())->toBe(9);
});

test('CP-10: el checkout se rechaza en el cierre exacto', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 20:00:00'));
    p1HorarioDeHoy('08:00:00', '20:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.carrito'))
        ->assertSessionHas('error');

    expect(Pedido::count())->toBe(0)
        ->and($producto->disponibleEn())->toBe(10)
        ->and(session('carrito'))->toBe([$producto->id => 1]);
});

test('CP-11: un horario que cruza la medianoche permite pedir de noche', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 23:30:00'));
    p1HorarioDeHoy('20:00:00', '02:00:00'); // abre 20:00, cierra 02:00 del día siguiente

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.pedidos'));

    expect(Pedido::count())->toBe(1)
        ->and($producto->disponibleEn())->toBe(9);
});

test('CP-11: un horario que cruza la medianoche sigue abierto de madrugada', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 01:30:00'));
    p1HorarioDeHoy('20:00:00', '02:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.pedidos'));

    expect(Pedido::count())->toBe(1);
});

test('CP-11: un horario que cruza la medianoche rechaza pasadas las 2:00', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 03:00:00'));
    p1HorarioDeHoy('20:00:00', '02:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.carrito'))
        ->assertSessionHas('error');

    expect(Pedido::count())->toBe(0)
        ->and($producto->disponibleEn())->toBe(10);
});

test('sin horario para hoy el restaurante se trata como cerrado', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));

    Horario::where('dia', now()->isoWeekday())->delete();

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $this->actingAs($cliente)
        ->post(route('cliente.checkout'), p1DatosCheckout())
        ->assertRedirect(route('cliente.carrito'))
        ->assertSessionHas(
            'error',
            'El restaurante está cerrado hoy porque no hay horario de atención configurado para este día.'
        );

    expect(Pedido::count())->toBe(0)
        ->and($producto->disponibleEn())->toBe(10);
});

test('el carrito avisa y deshabilita el botón de confirmar fuera de horario', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 03:00:00'));
    p1HorarioDeHoy('08:00:00', '20:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $respuesta = $this->actingAs($cliente)->get(route('cliente.carrito'));

    $respuesta->assertOk()->assertSee('El restaurante está cerrado.');

    expect($respuesta->getContent())->toContain(' disabled');
});

test('el carrito mantiene el botón de confirmar activo dentro del horario', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));
    p1HorarioDeHoy('08:00:00', '20:00:00');

    $cliente = p1Cliente();
    $producto = $this->crearProducto(10);

    $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

    $respuesta = $this->actingAs($cliente)->get(route('cliente.carrito'));

    $respuesta->assertOk();

    expect($respuesta->getContent())->not->toContain(' disabled');
});
