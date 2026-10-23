<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaTest extends TestCase
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

    private function producto(float $precio = 50.00, int $stock = 20): Producto
    {
        return $this->crearProducto($stock, ['precio' => $precio]);
    }

    private function pedidoEntregado(float $total = 80.00): Pedido
    {
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        return Pedido::create([
            'user_id' => $cliente->id,
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => $total,
            'direccion_entrega' => 'Calle Falsa 123',
            'telefono_contacto' => '70000000',
        ]);
    }

    private function ventaEnCaja(Caja $caja, float $total = 50.00): Venta
    {
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        return Venta::create([
            'user_id' => $cliente->id,
            'caja_id' => $caja->id,
            'estado' => Venta::PAGADA,
            'total' => $total,
            'metodo_pago' => Venta::PAGO_EFECTIVO,
            'monto_recibido' => $total,
            'cambio' => 0,
            'atendido_por' => $caja->user_id,
        ]);
    }

    public function test_el_cajero_abre_su_caja_con_el_monto_inicial(): void
    {
        $cajero = $this->cajero();

        $this->actingAs($cajero)
            ->post(route('cajero.caja.store'), [
                'monto_inicial' => 250.00,
                'observacion_apertura' => 'Fondo de apertura del día',
            ])
            ->assertRedirect();

        $caja = Caja::sole();

        $this->assertSame($cajero->id, $caja->user_id);
        $this->assertSame('250.00', $caja->monto_inicial);
        $this->assertTrue($caja->estaAbierta());
        $this->assertNotNull($caja->apertura_at);
    }

    public function test_no_se_puede_tener_dos_cajas_abiertas_a_la_vez(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.store'), ['monto_inicial' => 50])
            ->assertSessionHasErrors('monto_inicial');

        $this->assertSame(1, Caja::count());
    }
    public function test_despues_de_cerrar_se_puede_abrir_otra_caja_el_mismo_dia(): void
    {
        $cajero = $this->cajero();
        $primera = $this->abrirCaja($cajero, 100.00);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $primera), ['total_declarado' => 100.00]);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.store'), ['monto_inicial' => 20.00])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Caja::count());
        $this->assertSame(1, Caja::abiertaDeHoy($cajero->id) ? 1 : 0);
    }

    public function test_dos_cajeros_pueden_tener_caja_el_mismo_dia(): void
    {
        $uno = $this->cajero();
        $otro = $this->cajero();

        $this->actingAs($uno)->post(route('cajero.caja.store'), ['monto_inicial' => 100])->assertRedirect();
        $this->actingAs($otro)->post(route('cajero.caja.store'), ['monto_inicial' => 200])->assertRedirect();

        $this->assertSame(2, Caja::count());
    }

    public function test_la_cocina_no_puede_manejar_caja(): void
    {
        $cocina = User::factory()->cocina()->create();

        $this->actingAs($cocina)
            ->get(route('cajero.caja.create'))
            ->assertForbidden();

        $this->actingAs($cocina)
            ->post(route('cajero.caja.store'), ['monto_inicial' => 100])
            ->assertForbidden();

        $this->assertSame(0, Caja::count());
    }

    public function test_no_se_registra_una_venta_sin_caja_abierta(): void
    {
        $cajero = $this->cajero();
        $producto = $this->producto();

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            ])
            ->assertRedirect(route('cajero.caja.create'))
            ->assertSessionHas('error');

        $this->assertSame(0, Pedido::count());
    }

    public function test_la_venta_de_salon_entra_a_la_caja_abierta(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);
        $producto = $this->producto(35.00);

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
                'metodo_pago' => Venta::PAGO_EFECTIVO,
                'monto_recibido' => 100.00,
            ])
            ->assertRedirect(route('cajero.ventas.index'));
        $venta = Venta::sole();
        $this->assertSame(0, Pedido::count());
        $this->assertSame($caja->id, $venta->caja_id);
        $this->assertTrue($venta->estaPagada());
        $this->assertSame('70.00', $venta->total);
        $this->assertSame('100.00', $venta->monto_recibido);
        $this->assertSame('30.00', $venta->cambio);
        $this->assertSame(1, $venta->items()->count());
        $this->assertSame(2, $venta->items()->sole()->cantidad);
        $this->assertSame('35.00', $venta->items()->sole()->precio_unitario);
        $this->assertSame(18, $producto->disponibleEn());
        $this->assertSame(2, StockJornada::de($producto->id)->consumidos);
    }

    public function test_no_se_puede_cobrar_menos_de_lo_que_vale_la_venta(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);
        $producto = $this->producto(35.00);

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
                'metodo_pago' => Venta::PAGO_EFECTIVO,
                'monto_recibido' => 50.00,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Venta::count());
        $this->assertSame(20, $producto->disponibleEn());
        $this->assertSame(0, StockJornada::de($producto->id)->consumidos);
    }

    public function test_el_cobro_de_delivery_calcula_el_cambio_en_el_servidor(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);
        $pedido = $this->pedidoEntregado(80.00);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), [
                'metodo_pago' => Pedido::PAGO_EFECTIVO,
                'monto_recibido' => 100.00,
            ])
            ->assertRedirect(route('cajero.caja.show', $caja));

        $pedido->refresh();

        $this->assertSame($caja->id, $pedido->caja_id);
        $this->assertTrue($pedido->estaCobrado());
        $this->assertSame('20.00', $pedido->cambio);
        $this->assertSame(180.00, $caja->resumen()['esperado']);
    }
    public function test_el_cambio_se_ignora_si_via_en_el_formulario(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);
        $pedido = $this->pedidoEntregado(80.00);

        $this->actingAs($cajero)->post(route('cajero.caja.cobrar', $pedido), [
            'metodo_pago' => Pedido::PAGO_EFECTIVO,
            'monto_recibido' => 80.00,
            'cambio' => 999.00,
            'total' => 1.00,
        ]);

        $pedido->refresh();

        $this->assertSame('0.00', $pedido->cambio);
        $this->assertSame('80.00', (string) $pedido->total);
    }

    public function test_no_se_puede_cobrar_sin_caja_abierta(): void
    {
        $cajero = $this->cajero();
        $pedido = $this->pedidoEntregado();

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), ['metodo_pago' => Pedido::PAGO_QR])
            ->assertSessionHasErrors('metodo_pago');

        $this->assertFalse($pedido->fresh()->estaCobrado());
    }

    public function test_un_pedido_no_se_cobra_dos_veces(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);
        $pedido = $this->pedidoEntregado();

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), ['metodo_pago' => Pedido::PAGO_QR]);

        $primeraCaja = $pedido->fresh()->caja_id;

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), ['metodo_pago' => Pedido::PAGO_QR])
            ->assertSessionHasErrors('metodo_pago');

        $this->assertSame($primeraCaja, $pedido->fresh()->caja_id);
    }

    public function test_no_se_cobra_un_pedido_cancelado(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);

        $pedido = $this->pedidoEntregado();
        $pedido->update(['estado' => Pedido::ESTADO_CANCELADO]);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), ['metodo_pago' => Pedido::PAGO_EFECTIVO, 'monto_recibido' => 80])
            ->assertSessionHasErrors('metodo_pago');

        $this->assertFalse($pedido->fresh()->estaCobrado());
    }

    public function test_el_cobro_no_acepta_menos_dinero_del_total(): void
    {
        $cajero = $this->cajero();
        $this->abrirCaja($cajero);
        $pedido = $this->pedidoEntregado(80.00);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), [
                'metodo_pago' => Pedido::PAGO_EFECTIVO,
                'monto_recibido' => 50.00,
            ])
            ->assertSessionHasErrors('monto_recibido');

        $this->assertFalse($pedido->fresh()->estaCobrado());
    }
    public function test_el_arqueo_suma_ventas_cobros_movimientos_y_descuenta_salidas(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $this->ventaEnCaja($caja, 60.00);
        $this->ventaEnCaja($caja, 60.00);

        $cobro = $this->pedidoEntregado(80.00);
        $cobro->update(['caja_id' => $caja->id, 'pagado_at' => now()]);

        $caja->movimientos()->create([
            'tipo' => CajaMovimiento::ENTRADA,
            'monto' => 30.00,
            'concepto' => 'Cambio devuelto por un cliente',
            'user_id' => $cajero->id,
        ]);
        $caja->movimientos()->create([
            'tipo' => CajaMovimiento::SALIDA,
            'monto' => 25.00,
            'concepto' => 'Retiro a la boveda del turno',
            'user_id' => $cajero->id,
        ]);

        $resumen = $caja->fresh()->resumen();

        $this->assertSame(120.00, $resumen['ventas']);
        $this->assertSame(80.00, $resumen['cobros']);
        $this->assertSame(200.00, $resumen['ingresos']);
        $this->assertSame(30.00, $resumen['entradas']);
        $this->assertSame(25.00, $resumen['salidas']);

        $this->assertSame(305.00, $resumen['esperado']);
    }

    public function test_el_arqueo_no_cuenta_pedidos_sin_cobrar_ni_cancelados(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);
        $sinCobrar = $this->pedidoEntregado(70.00);
        $sinCobrar->update(['caja_id' => $caja->id]);
        $cancelado = $this->pedidoEntregado(90.00);
        $cancelado->update([
            'caja_id' => $caja->id,
            'pagado_at' => now(),
            'estado' => Pedido::ESTADO_CANCELADO,
        ]);
        $anulada = $this->ventaEnCaja($caja, 45.00);
        $anulada->update(['estado' => Venta::ANULADA]);
        $resumen = $caja->fresh()->resumen();
        $this->assertSame(0.0, $resumen['ventas']);
        $this->assertSame(0.0, $resumen['cobros']);
        $this->assertSame(100.00, $resumen['esperado']);
    }

    public function test_otro_cajero_no_puede_registrar_movimientos_en_una_caja_que_no_es_suya(): void
    {
        $cajero = $this->cajero();
        $otro = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $this->actingAs($otro)
            ->get(route('cajero.caja.movimiento.create', $caja))
            ->assertForbidden();

        $this->actingAs($otro)
            ->post(route('cajero.caja.movimiento.store', $caja), [
                'tipo' => CajaMovimiento::SALIDA,
                'monto' => 500,
                'concepto' => 'Intento de retiro ajeno',
            ])
            ->assertForbidden();

        $this->assertSame(0, CajaMovimiento::count());
    }

    public function test_el_cierre_guarda_el_arqueo_y_detecta_el_descuadre(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $pedido = $this->pedidoEntregado(60.00);
        $pedido->update(['caja_id' => $caja->id, 'pagado_at' => now()]);
        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja), ['total_declarado' => 155.00])
            ->assertRedirect(route('cajero.caja.show', $caja))
            ->assertSessionHas('warning');

        $caja->refresh();

        $this->assertSame(Caja::CERRADA, $caja->estado);
        $this->assertSame('160.00', $caja->total_esperado);
        $this->assertSame('155.00', $caja->total_declarado);
        $this->assertSame('-5.00', $caja->diferencia);
        $this->assertFalse($caja->cuadra());
        $this->assertNotNull($caja->cierre_at);
        $this->assertSame('0.00', $caja->total_ventas);
        $this->assertSame('60.00', $caja->total_cobros);
    }

    public function test_un_arqueo_que_cuadra_no_avisa_nada(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $pedido = $this->pedidoEntregado(60.00);
        $pedido->update(['caja_id' => $caja->id, 'pagado_at' => now()]);
        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja), ['total_declarado' => 160.00])
            ->assertSessionHas('success')
            ->assertSessionMissing('warning');

        $this->assertTrue($caja->fresh()->cuadra());
    }

    public function test_una_caja_cerrada_no_admite_mas_movimientos_ni_ventas(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);
        $producto = $this->producto();

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja), ['total_declarado' => 100.00]);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.movimiento.store', $caja->fresh(), [
                'tipo' => CajaMovimiento::SALIDA,
                'monto' => 10,
                'concepto' => 'Retiro posterior al cierre',
            ]))
            ->assertSessionHasErrors('monto');

        $this->assertSame(0, CajaMovimiento::count());

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => User::factory()->create(['role' => User::ROL_CLIENTE])->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Venta::count());
    }

    public function test_no_se_puede_cerrar_una_caja_dos_veces(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja), ['total_declarado' => 100.00]);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja->fresh(), ['total_declarado' => 500.00]))
            ->assertSessionHasErrors('total_declarado');

        $this->assertSame('100.00', $caja->fresh()->total_declarado);
    }

    public function test_el_arqueo_cerrado_no_se_recalcula_si_mas_adentro_se_cobra_un_pedido(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja), ['total_declarado' => 100.00]);

        $cajaVieja = $caja->fresh();
        $this->abrirCaja($cajero, 0.00);

        $pedido = $this->pedidoEntregado(80.00);
        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), ['metodo_pago' => Pedido::PAGO_QR]);

        $this->assertEqualsWithDelta(100.00, $cajaVieja->arqueo()['esperado'], 0.001);
        $this->assertNotSame($cajaVieja->id, $pedido->fresh()->caja_id);
    }

    public function test_el_cajero_solo_ve_sus_cajas_y_el_administrador_ve_todas(): void
    {
        $cajero = $this->cajero();
        $otro = $this->cajero();
        $propia = $this->abrirCaja($cajero, 100.00);
        $ajena = $this->abrirCaja($otro, 200.00);
        $this->actingAs($cajero)
            ->get(route('cajero.caja.show', $propia))
            ->assertOk();
        $this->actingAs($cajero)
            ->get(route('cajero.caja.show', $ajena))
            ->assertForbidden();
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.caja.show', $propia))
            ->assertOk();
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.caja.show', $ajena))
            ->assertOk();
    }

    public function test_el_listado_de_cajas_no_filtra_a_los_demas_del_restaurante(): void
    {
        $cajero = $this->cajero();
        $otro = $this->cajero();
        $this->abrirCaja($cajero, 100.00);
        $this->abrirCaja($otro, 200.00);

        $cajas = $this->actingAs($cajero)
            ->get(route('cajero.caja.index'))
            ->assertOk()
            ->viewData('cajas');

        $this->assertCount(1, $cajas);
        $this->assertSame($cajero->id, $cajas->first()->user_id);
    }
    public function test_el_dashboard_del_cajero_avisa_que_no_abrio_caja(): void
    {
        $this->actingAs($this->cajero())
            ->get(route('cajero.dashboard'))
            ->assertOk()
            ->assertSee('caja de hoy', false);
    }
}