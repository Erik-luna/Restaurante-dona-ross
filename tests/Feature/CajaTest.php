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

/**
 * Pruebas del módulo de caja.
 *
 * La caja es la parte del sistema donde un error se nota en el bolsillo: si una
 * venta entra sin caja, o si el arqueo " cuadra" porque el sistema cambió el número
 * por el que le dio, el descuadre aparece en el dinero real. Por eso las pruebas
 * se centran en tres cosas: que no se pueda vender ni cobrar sin caja, que el
 * arqueo calcule lo que debe calcular, y que un cierre no se pueda repetir ni
 * reescribir.
 */
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
        // El stock va en la jornada de hoy, no en el producto: es crearProducto()
        // del TestCase base quien abre esa jornada.
        return $this->crearProducto($stock, ['precio' => $precio]);
    }

    /** Pedido de delivery entregado y sin cobrar: el caso típico del cobro. */
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

    /**
     * Venta de salón ya cobrada en una caja concreta.
     *
     * Es una entidad propia (tabla `ventas`), no un pedido con tipo 'local': por eso
     * se crea directamente y no por el formulario.
     */
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

// ---------------------------------------------------------------- apertura

    /**
     * La caja de la prueba se consulta con sole() en vez de por un id fijo: el id
     * depende del orden en que corran las pruebas dentro del archivo.
     */
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

/**
     * Cerrar la caja y abrir otra el mismo día sí se permite: el turno puede partirse
     * en dos, y cada mitad lleva su propio arqueo.
     */
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

        // Cada turno responde por su propio dinero: por eso el índice único es
        // (user_id, fecha) y no sólo fecha.
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

    // ------------------------------------------------------------------ ventas

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

        // La venta de salón vive en su propia tabla, no en `pedidos`.
        $venta = Venta::sole();
        $this->assertSame(0, Pedido::count());

        $this->assertSame($caja->id, $venta->caja_id);
        $this->assertTrue($venta->estaPagada());
        // 2 x 35 = 70. El cliente entregó 100, el cambio son 30.
        $this->assertSame('70.00', $venta->total);
        $this->assertSame('100.00', $venta->monto_recibido);
        $this->assertSame('30.00', $venta->cambio);

        // Sus líneas van también en su propia tabla.
        $this->assertSame(1, $venta->items()->count());
        $this->assertSame(2, $venta->items()->sole()->cantidad);
        $this->assertSame('35.00', $venta->items()->sole()->precio_unitario);

        // Y el stock se descontó de la jornada de hoy: 20 cargadas menos 2 vendidas.
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

        // La venta no se guardó, así que tampoco debe haber salido stock: la
        // comprobación y el descuento tienen que ser la misma operación.
        $this->assertSame(20, $producto->disponibleEn());
        $this->assertSame(0, StockJornada::de($producto->id)->consumidos);
    }

    // -------------------------------------------------------------------- cobro

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

        // El dinero del cobro cuenta para el arqueo.
        $this->assertSame(180.00, $caja->resumen()['esperado']);
    }

    /**
     * El cambio nunca se recibe del formulario.
     *
     * Si el navegador mandara su propio campo 'cambio', bastaría con escribir un
     * número alto para que el sistemaPagara de más. Se mandan campos que el
     * modelo ni tiene y aun así el cambio se calcula solo.
     */
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

    // --------------------------------------------------------------- arqueo

    /**
     * El arqueo suma las ventas de salón y los cobros de delivery por separado, y
     * descuenta las salidas.
     */
    public function test_el_arqueo_suma_ventas_cobros_movimientos_y_descuenta_salidas(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        // Dos ventas de salón por 60.
        $this->ventaEnCaja($caja, 60.00);
        $this->ventaEnCaja($caja, 60.00);

        // Y un cobro de delivery por 80: dinero real, pero de otra operación.
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

        // Las dos fuentes de dinero se informan por separado y suman 200.
        $this->assertSame(120.00, $resumen['ventas']);
        $this->assertSame(80.00, $resumen['cobros']);
        $this->assertSame(200.00, $resumen['ingresos']);
        $this->assertSame(30.00, $resumen['entradas']);
        $this->assertSame(25.00, $resumen['salidas']);

        // 100 inicial + 120 ventas + 80 cobros + 30 entrada - 25 salida
        $this->assertSame(305.00, $resumen['esperado']);
    }

    public function test_el_arqueo_no_cuenta_pedidos_sin_cobrar_ni_cancelados(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        // Cocinado pero todavía no cobrado: su dinero aún no está en el cajón.
        $sinCobrar = $this->pedidoEntregado(70.00);
        $sinCobrar->update(['caja_id' => $caja->id]);

        // Cobrado y luego cancelado: el dinero se devolvió.
        $cancelado = $this->pedidoEntregado(90.00);
        $cancelado->update([
            'caja_id' => $caja->id,
            'pagado_at' => now(),
            'estado' => Pedido::ESTADO_CANCELADO,
        ]);

        // Una venta anulada tampoco cuenta: se preparó el plato pero no se cobró.
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

        // Ni siquiera ve el formulario: la caja de otro cajero no le pertenece, y
        // el administrador es quien puede revisarla.
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

    // ---------------------------------------------------------------- cierre

    public function test_el_cierre_guarda_el_arqueo_y_detecta_el_descuadre(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $pedido = $this->pedidoEntregado(60.00);
        $pedido->update(['caja_id' => $caja->id, 'pagado_at' => now()]);

        // El sistema espera 100 + 60 = 160, pero en el cajón hay 155.
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

        // El cobro de delivery queda guardado aparte de las ventas, para que un
        // descuadre se pueda rastrear hasta su origen.
        $this->assertSame('0.00', $caja->total_ventas);
        $this->assertSame('60.00', $caja->total_cobros);
    }

    public function test_un_arqueo_que_cuadra_no_avisa_nada(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $pedido = $this->pedidoEntregado(60.00);
        $pedido->update(['caja_id' => $caja->id, 'pagado_at' => now()]);

        // El sistema espera 100 + 60 = 160, pero en el cajón hay 155.
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

        // Y el registro de venta también se rechaza, porque no hay caja abierta.
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

        // El primer cierre manda: no se puede reescribir un arqueo ya cerrado.
        $this->assertSame('100.00', $caja->fresh()->total_declarado);
    }

    public function test_el_arqueo_cerrado_no_se_recalcula_si_mas_adentro_se_cobra_un_pedido(): void
    {
        $cajero = $this->cajero();
        $caja = $this->abrirCaja($cajero, 100.00);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja), ['total_declarado' => 100.00]);

        // Se abre una caja nueva y se cobra allí el pedido pendiente.
        $cajaVieja = $caja->fresh();
        $this->abrirCaja($cajero, 0.00);

        $pedido = $this->pedidoEntregado(80.00);
        $this->actingAs($cajero)
            ->post(route('cajero.caja.cobrar', $pedido), ['metodo_pago' => Pedido::PAGO_QR]);

        // La caja cerrada conserva su número aunque el total del pedido haya
        // cambiado: su arqueo es una foto histórica.
        $this->assertEqualsWithDelta(100.00, $cajaVieja->arqueo()['esperado'], 0.001);
        $this->assertNotSame($cajaVieja->id, $pedido->fresh()->caja_id);
    }

    // -------------------------------------------------------------- visibilidad

    public function test_el_cajero_solo_ve_sus_cajas_y_el_administrador_ve_todas(): void
    {
        $cajero = $this->cajero();
        $otro = $this->cajero();
        $propia = $this->abrirCaja($cajero, 100.00);
        $ajena = $this->abrirCaja($otro, 200.00);

        // El cajero ve la suya...
        $this->actingAs($cajero)
            ->get(route('cajero.caja.show', $propia))
            ->assertOk();

        // ...pero no la del compañero.
        $this->actingAs($cajero)
            ->get(route('cajero.caja.show', $ajena))
            ->assertForbidden();

        // El administrador ve las dos.
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

    /**
     * Comprueba un fragmento sin tildes: segun como se codifique la respuesta,
     * buscar la palabra acentuada daria falsos negativos.
     */
    public function test_el_dashboard_del_cajero_avisa_que_no_abrio_caja(): void
    {
        $this->actingAs($this->cajero())
            ->get(route('cajero.dashboard'))
            ->assertOk()
            ->assertSee('caja de hoy', false);
    }
}