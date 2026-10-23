<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    private function cajero(): User
    {
        return User::factory()->cajero()->create();
    }

    private function abrirCaja(User $cajero, float $monto = 100.00): Caja
    {
        return Caja::create([
            'user_id' => $cajero->id,
            'fecha' => today(),
            'monto_inicial' => $monto,
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
        ]);
    }

    private function producto(float $precio = 35.00, int $stock = 20): Producto
    {
        return $this->crearProducto($stock, ['precio' => $precio]);
    }

    public function test_la_venta_se_guarda_en_su_propia_tabla_y_no_en_pedidos(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);
        $producto = $this->producto();

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
                'metodo_pago' => Venta::PAGO_EFECTIVO,
                'monto_recibido' => 35.00,
            ])
            ->assertRedirect(route('cajero.ventas.index'));

        $this->assertSame(1, Venta::count());
        $this->assertSame(0, Pedido::count());
    }

    public function test_las_lineas_de_venta_guardan_el_precio_con_el_que_se_vendio(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero);
        $producto = $this->producto(35.00);

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
                'metodo_pago' => Venta::PAGO_EFECTIVO,
                'monto_recibido' => 70.00,
            ]);

        $item = VentaItem::sole();

        $this->assertSame('35.00', $item->precio_unitario);
        $this->assertSame('70.00', $item->subtotal);
        $this->assertSame($caja->id, $item->venta->caja_id);

        $producto->update(['precio' => 50.00]);

        $this->assertSame('35.00', $item->fresh()->precio_unitario);
        $this->assertSame('70.00', Venta::sole()->total);
    }

    public function test_una_venta_nace_pagada_y_con_caja(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero);

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $this->producto(20.00)->id, 'cantidad' => 1]],
                'metodo_pago' => Venta::PAGO_QR,
            ]);

        $venta = Venta::sole();
        $this->assertTrue($venta->estaPagada());
        $this->assertSame($caja->id, $venta->caja_id);
        $this->assertSame(Venta::PAGO_QR, $venta->metodo_pago);
        $this->assertNull($venta->monto_recibido);
        $this->assertNull($venta->cambio);
    }

    public function test_la_venta_no_aparece_en_el_listado_de_pedidos_de_delivery(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $this->producto()->id, 'cantidad' => 1]],
                'metodo_pago' => Venta::PAGO_EFECTIVO,
                'monto_recibido' => 35.00,
            ]);

        $pedidos = $this->actingAs($cajero)
            ->get(route('cajero.pedidos.index'))
            ->assertOk()
            ->viewData('pedidos');

        $this->assertCount(0, $pedidos);
    }

    public function test_la_cocina_no_ve_las_ventas_de_salon(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero);

        Venta::create([
            'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
            'caja_id' => $caja->id,
            'estado' => Venta::PAGADA,
            'total' => 50.00,
            'metodo_pago' => Venta::PAGO_EFECTIVO,
            'monto_recibido' => 50.00,
            'cambio' => 0,
            'atendido_por' => $cajero->id,
        ]);

        $pedidos = $this->actingAs(User::factory()->cocina()->create())
            ->get(route('cocina.dashboard'))
            ->assertOk()
            ->viewData('pedidos');

        $this->assertCount(0, $pedidos);
    }

    public function test_un_producto_con_historial_de_venta_no_se_borra_solo_se_desactiva(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero);
        $producto = $this->producto();

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
                'metodo_pago' => Venta::PAGO_EFECTIVO,
                'monto_recibido' => 35.00,
            ]);

        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->delete(route('admin.productos.destroy', $producto))
            ->assertRedirect(route('admin.productos.index'));
        $this->assertNotNull($producto->fresh());
        $this->assertFalse($producto->fresh()->activo);
        $this->assertSame(1, VentaItem::where('producto_id', $producto->id)->count());
    }
}