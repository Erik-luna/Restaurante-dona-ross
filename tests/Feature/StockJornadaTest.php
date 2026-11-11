<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Models\User;
use App\Models\Venta;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
class StockJornadaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->administrador()->create(['activo' => true]);
    }

    private function cocina(): User
    {
        return User::factory()->cocina()->create(['activo' => true]);
    }

    private function cliente(): User
    {
        return User::factory()->create(['role' => User::ROL_CLIENTE, 'activo' => true]);
    }


    public function test_la_disponibilidad_es_inicial_mas_entradas_menos_mermas_y_consumidos(): void
    {
        $producto = $this->crearProducto(50);

        $jornada = StockJornada::de($producto->id);

        $this->assertSame(50, $jornada->disponible());

        $jornada->reponer(10);
        $this->assertSame(60, $jornada->disponible());

        $jornada->registrarMerma(5, 'Se cayó una bandeja.');
        $this->assertSame(55, $jornada->disponible());

        $jornada->consumir(3);
        $this->assertSame(52, $jornada->disponible());
    }

    public function test_el_sql_y_el_modelo_coinciden(): void
    {
        $producto = $this->crearProducto(30);
        $jornada = StockJornada::de($producto->id);

        $jornada->reponer(12, 'Llegó más del proveedor.');
        $jornada->registrarMerma(2, 'Uno roto.');
        $jornada->consumir(7);

        $this->assertSame(
            $jornada->disponible(),
            $producto->disponibleEn(),
            'La fórmula de SQL y la del modelo dan números distintos: el catálogo y el carrito no se pondrían de acuerdo.'
        );

        $conStock = Producto::where('activo', true)->conDisponible()->pluck('id')->all();
        $this->assertSame([$producto->id], $conStock);

        $this->assertSame(
            [],
            Producto::where('activo', true)->conDisponible(null, 34)->pluck('id')->all()
        );
    }

    public function test_la_disponibilidad_de_una_jornada_pasada_no_afecta_a_hoy(): void
    {
        $producto = $this->crearProducto(10);
        $this->cargarStock($producto, 40, now()->subDay()->toDateString());

        $this->assertSame(40, $producto->disponibleEn(now()->subDay()->toDateString()));
        $this->assertSame(10, $producto->disponibleEn());
    }

    public function test_cargar_el_dia_pisa_el_inicial_y_reponer_lo_suma(): void
    {
        $cocina = $this->cocina();
        $producto = $this->crearProducto(20);

        $this->actingAs($cocina)->put(route('cocina.stock.update', $producto), [
            'accion' => StockJornada::ACCION_CARGAR,
            'cantidad' => 25,
        ])->assertRedirect(route('cocina.stock.index'));

        $this->assertSame(25, StockJornada::de($producto->id)->inicial);
        $this->assertSame(25, $producto->disponibleEn());

        $this->actingAs($cocina)->put(route('cocina.stock.update', $producto), [
            'accion' => StockJornada::ACCION_REPONER,
            'cantidad' => 15,
            'motivo' => 'Llegó del proveedor.',
        ])->assertRedirect(route('cocina.stock.index'));

        $jornada = StockJornada::de($producto->id);

        $this->assertSame(25, $jornada->inicial, 'La reposición no debe tocar la carga inicial.');
        $this->assertSame(15, $jornada->entradas);
        $this->assertSame(40, $producto->disponibleEn());
    }

    public function test_una_merma_exige_motivo(): void
    {
        $cocina = $this->cocina();
        $producto = $this->crearProducto(20);

        $this->actingAs($cocina)->put(route('cocina.stock.update', $producto), [
            'accion' => StockJornada::ACCION_MERMA,
            'cantidad' => 4,
        ])->assertSessionHasErrors('motivo');

        $this->assertSame(0, StockJornada::de($producto->id)->mermas);
    }

    public function test_una_acion_desconocida_se_rechaza(): void
    {
        $cocina = $this->cocina();
        $producto = $this->crearProducto(20);

        $this->actingAs($cocina)->put(route('cocina.stock.update', $producto), [
            'accion' => 'inventar-cero',
            'cantidad' => 5,
        ])->assertSessionHasErrors('accion');

        $this->assertSame(20, $producto->disponibleEn());
    }

    public function test_no_se_puede_cargar_stock_para_un_dia_futuro(): void
    {
        $admin = $this->admin();
        $producto = $this->crearProducto(20);

        $this->actingAs($admin)->put(route('admin.stock.update', $producto), [
            'accion' => StockJornada::ACCION_CARGAR,
            'cantidad' => 30,
            'fecha' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('fecha');

        $this->assertSame(20, $producto->disponibleEn());
    }

    public function test_se_puede_corregir_el_stock_de_ayer(): void
    {
        $admin = $this->admin();
        $producto = $this->crearProducto(10);
        $ayer = now()->subDay()->toDateString();

        $this->actingAs($admin)->put(route('admin.stock.update', $producto), [
            'accion' => StockJornada::ACCION_CARGAR,
            'cantidad' => 35,
            'fecha' => $ayer,
        ])->assertRedirect(route('admin.stock.index'));

        $this->assertSame(35, $producto->disponibleEn($ayer));
        $this->assertSame(10, $producto->disponibleEn());
    }

    public function test_el_cliente_no_puede_ajustar_el_stock(): void
    {
        $cliente = $this->cliente();
        $producto = $this->crearProducto(20);

        $this->actingAs($cliente)->put(route('admin.stock.update', $producto), [
            'accion' => StockJornada::ACCION_CARGAR,
            'cantidad' => 999,
        ])->assertForbidden();

        $this->assertSame(20, $producto->disponibleEn());
    }

    public function test_no_se_puede_vender_mas_de_lo_que_hay(): void
    {
        $producto = $this->crearProducto(2);
        $stock = new StockService;

        $this->expectException(RuntimeException::class);

        $stock->descontar([['producto' => $producto, 'cantidad' => 3]]);
    }

    public function test_el_mensaje_de_stock_insuficiente_dice_lo_que_anda_y_lo_que_se_pidio(): void
    {
        $producto = $this->crearProducto(2);
        $stock = new StockService;

        try {
            $stock->descontar([['producto' => $producto, 'cantidad' => 5]]);
            $this->fail('Debió lanzar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($producto->nombre, $e->getMessage());
            $this->assertStringContainsString('quedan 2', $e->getMessage());
            $this->assertStringContainsString('se pidieron 5', $e->getMessage());
        }
    }

    public function test_vender_no_toca_la_jornada_de_otro_dia(): void
    {
        $producto = $this->crearProducto(20);
        $this->cargarStock($producto, 20, now()->subDay()->toDateString());

        $stock = new StockService;
        $stock->descontar([['producto' => $producto, 'cantidad' => 4]]);

        $this->assertSame(16, $producto->disponibleEn());
        $this->assertSame(20, $producto->disponibleEn(now()->subDay()->toDateString()));
    }

    public function test_el_pedido_de_delivery_descuenta_el_stock_al_confirmarse(): void
    {
        // P1: el checkout exige que el restaurante esté abierto; se abre todo
        // el día para que esta prueba no dependa de la hora en que se ejecute.
        Horario::where('dia', now()->isoWeekday())
            ->update(['apertura' => '00:00:00', 'cierre' => '23:59:59', 'activo' => true]);

        $cliente = $this->cliente();
        $producto = $this->crearProducto(10);

        $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));

        $this->actingAs($cliente)
            ->post(route('cliente.checkout'), [
                'direccion_entrega' => 'Calle Falsa 123',
                'telefono_contacto' => '70000000',
            ])
            ->assertRedirect(route('cliente.pedidos.show', Pedido::latest('id')->first()));

        $this->assertSame(1, Pedido::count());
        $this->assertSame(9, $producto->disponibleEn());
    }

    public function test_el_carrito_no_acepta_mas_que_la_disponibilidad_del_dia(): void
    {
        $cliente = $this->cliente();
        $producto = $this->crearProducto(2);

        $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));
        $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));
        $this->actingAs($cliente)
            ->post(route('cliente.carrito.add', $producto))
            ->assertSessionHas('error');

        $this->actingAs($cliente)
            ->put(route('cliente.carrito.update', $producto), ['cantidad' => 50])
            ->assertSessionHas('error');
    }

    public function test_un_pedido_que_no_alcanza_no_se_guarda_ni_descuenta(): void
    {
        // P1: esta prueba verifica el rechazo por stock, no por horario de
        // atención, así que el restaurante se deja abierto todo el día.
        Horario::where('dia', now()->isoWeekday())
            ->update(['apertura' => '00:00:00', 'cierre' => '23:59:59', 'activo' => true]);

        $cliente = $this->cliente();
        $producto = $this->crearProducto(5);

        $this->actingAs($cliente)->post(route('cliente.carrito.add', $producto));
        StockJornada::de($producto->id)->consumir(5);

        $this->actingAs($cliente)
            ->post(route('cliente.checkout'), [
                'direccion_entrega' => 'Calle Falsa 123',
                'telefono_contacto' => '70000000',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Pedido::count());
        $this->assertSame(0, $producto->disponibleEn());
    }

    public function test_la_venta_de_salon_descuenta_el_stock(): void
    {
        $cajero = User::factory()->cajero()->create(['activo' => true]);
        $this->abrirCaja($cajero);
        $producto = $this->crearProducto(20);

        $this->actingAs($cajero)
            ->post(route('cajero.ventas.store'), [
                'user_id' => $this->cliente()->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
                'metodo_pago' => Venta::PAGO_EFECTIVO,
                'monto_recibido' => 70.00,
            ])
            ->assertRedirect(route('cajero.ventas.index'));

        $this->assertSame(18, $producto->disponibleEn());
    }

    public function test_el_catalogo_no_ofrece_un_plato_agotado_pero_lo_deja_visible(): void
    {
        $this->crearProducto(0, ['nombre' => 'Sin Portiones']);
        $this->crearProducto(10, ['nombre' => 'Con Portiones']);

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee('Sin Portiones')
            ->assertSee('Agotado')
            ->assertSee('Con Portiones')
            ->assertSee('Quedan: 10');
    }

    public function test_un_producto_sin_carga_no_aparece_en_el_formulario_de_venta(): void
    {
        $cajero = User::factory()->cajero()->create(['activo' => true]);
        $this->abrirCaja($cajero);

        $this->crearProducto(0, ['nombre' => 'Agotado Totalmente']);
        $this->crearProducto(10, ['nombre' => 'Disponible']);

        $this->actingAs($cajero)
            ->get(route('cajero.ventas.create'))
            ->assertOk()
            ->assertSee('Disponible')
            ->assertDontSee('Agotado Totalmente');
    }

    public function test_la_pantalla_de_stock_muestra_el_detalle_de_la_jornada(): void
    {
        $cocina = $this->cocina();
        $producto = $this->crearProducto(40);

        $jornada = StockJornada::de($producto->id);
        $jornada->reponer(6);
        $jornada->registrarMerma(2, 'Uno roto.');
        $jornada->consumir(5);

        $this->actingAs($cocina)
            ->get(route('cocina.stock.index'))
            ->assertOk()
            ->assertSee('40')
            ->assertSee('6')
            ->assertSee('2')
            ->assertSee('5')
            ->assertSee('39');
    }
    private function abrirCaja(User $cajero): void
    {
        $this->actingAs($cajero)->post(route('cajero.caja.store'), [
            'monto_inicial' => 100.00,
        ]);
    }
}