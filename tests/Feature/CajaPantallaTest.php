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

/**
 * El detalle de caja tiene que pintarse con datos de las dos fuentes de dinero.
 *
 * El arqueo se calculó bien en las pruebas del módulo, pero si la vista mezclara
 * los dos listados o pidiera una columna que ya no existe, el error sale en
 * pantalla y no en el test: por eso se renderizan las pantallas con datos reales.
 */
class CajaPantallaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_detalle_de_caja_muestra_ventas_y_cobros_por_separado(): void
    {
        $cajero = User::factory()->cajero()->create();
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);
        // Estas pruebas escriben ventas y pedidos con los modelos para poder poner en
        // la pantalla cualquier cifra, así que no pasan por StockService. Por eso el
        // producto va sin jornada: el día que alguna venda de verdad, stock
        // --consumidos = 0 le daría error por falta de existencias.
        $producto = Producto::create([
            'nombre' => 'Silpancho',
            'descripcion' => 'Plato de la casa',
            'precio' => 35.00,
            'activo' => true,
            'destacado' => false,
        ]);

        $caja = Caja::create([
            'user_id' => $cajero->id,
            'fecha' => today(),
            'monto_inicial' => 100.00,
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
        ]);

        $venta = Venta::create([
            'user_id' => $cliente->id,
            'caja_id' => $caja->id,
            'estado' => Venta::PAGADA,
            'total' => 70.00,
            'metodo_pago' => Venta::PAGO_EFECTIVO,
            'monto_recibido' => 100.00,
            'cambio' => 30.00,
            'atendido_por' => $cajero->id,
        ]);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 35.00,
            'subtotal' => 70.00,
        ]);

        $pedido = Pedido::create([
            'user_id' => $cliente->id,
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => 80.00,
            'direccion_entrega' => 'Calle Falsa 123',
            'telefono_contacto' => '70000000',
            'caja_id' => $caja->id,
            'metodo_pago' => Pedido::PAGO_QR,
            'pagado_at' => now(),
        ]);

        $this->actingAs($cajero)
            ->get(route('cajero.caja.show', $caja))
            ->assertOk()
            ->assertSee('Ventas de salón')
            ->assertSee('Cobros de pedidos delivery')
            // Los dos importes aparecen por separado en sus tarjetas.
            ->assertSee('Bs. 70.00')
            ->assertSee('Bs. 80.00')
            // Y las dos tablas listan su operación.
            ->assertSee('#'.$venta->id)
            ->assertSee('#'.$pedido->id);
    }

    public function test_el_arqueo_se_pinta_cerrado_con_ventas_y_cobros_congelados(): void
    {
        $cajero = User::factory()->cajero()->create();
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $caja = Caja::create([
            'user_id' => $cajero->id,
            'fecha' => today(),
            'monto_inicial' => 100.00,
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
        ]);

        Venta::create([
            'user_id' => $cliente->id,
            'caja_id' => $caja->id,
            'estado' => Venta::PAGADA,
            'total' => 40.00,
            'metodo_pago' => Venta::PAGO_EFECTIVO,
            'monto_recibido' => 40.00,
            'cambio' => 0,
            'atendido_por' => $cajero->id,
        ]);

        $this->actingAs($cajero)
            ->post(route('cajero.caja.cierre.store', $caja), ['total_declarado' => 140.00])
            ->assertSessionHas('success');

        $this->actingAs($cajero)
            ->get(route('cajero.caja.show', $caja->fresh()))
            ->assertOk()
            ->assertSee('Arqueo cerrado: cuadró');
    }

    public function test_el_formulario_de_arqueo_desglosa_las_dos_fuentes_de_dinero(): void
    {
        $cajero = User::factory()->cajero()->create();
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $caja = Caja::create([
            'user_id' => $cajero->id,
            'fecha' => today(),
            'monto_inicial' => 0.00,
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
        ]);

        Pedido::create([
            'user_id' => $cliente->id,
            'estado' => Pedido::ESTADO_ENTREGADO,
            'total' => 80.00,
            'direccion_entrega' => 'Calle Falsa 123',
            'telefono_contacto' => '70000000',
            'caja_id' => $caja->id,
            'metodo_pago' => Pedido::PAGO_QR,
            'pagado_at' => now(),
        ]);

        // El cajero tiene que ver de dónde sale el número antes de contar el dinero.
        $this->actingAs($cajero)
            ->get(route('cajero.caja.cierre', $caja))
            ->assertOk()
            ->assertSee('Ventas de salón')
            ->assertSee('Cobros de pedidos delivery')
            ->assertSee('Bs. 80.00');
    }

    public function test_el_ticket_de_venta_muestra_el_caja_que_lo_cobro(): void
    {
        $cajero = User::factory()->cajero()->create();
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);
        // Estas pruebas escriben ventas y pedidos con los modelos para poder poner en
        // la pantalla cualquier cifra, así que no pasan por StockService. Por eso el
        // producto va sin jornada: el día que alguna venda de verdad, stock
        // --consumidos = 0 le daría error por falta de existencias.
        $producto = Producto::create([
            'nombre' => 'Silpancho',
            'descripcion' => 'Plato de la casa',
            'precio' => 35.00,
            'activo' => true,
            'destacado' => false,
        ]);

        $caja = Caja::create([
            'user_id' => $cajero->id,
            'fecha' => today(),
            'monto_inicial' => 100.00,
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
        ]);

        $venta = Venta::create([
            'user_id' => $cliente->id,
            'caja_id' => $caja->id,
            'estado' => Venta::PAGADA,
            'total' => 35.00,
            'metodo_pago' => Venta::PAGO_EFECTIVO,
            'monto_recibido' => 50.00,
            'cambio' => 15.00,
            'atendido_por' => $cajero->id,
        ]);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 35.00,
            'subtotal' => 35.00,
        ]);

        $this->actingAs($cajero)
            ->get(route('cajero.ventas.show', $venta))
            ->assertOk()
            ->assertSee('TICKET DE VENTA #'.$venta->id)
            ->assertSee('Cambio: Bs. 15.00');
    }

    public function test_una_venta_sin_caja_no_revienta_el_ticket(): void
    {
        // Las ventas anteriores a este módulo no tienen caja. La ficha tiene que
        // decirlo con claridad en vez de fallar al buscar la relación.
        $cajero = User::factory()->cajero()->create();

        $venta = Venta::create([
            'user_id' => null,
            'caja_id' => null,
            'estado' => Venta::PAGADA,
            'total' => 45.00,
            'metodo_pago' => Venta::PAGO_EFECTIVO,
            'monto_recibido' => 45.00,
            'cambio' => 0,
            'atendido_por' => $cajero->id,
        ]);

        $this->actingAs($cajero)
            ->get(route('cajero.ventas.show', $venta))
            ->assertOk()
            ->assertSee('Cliente de salón')
            ->assertSee('Sin caja');
    }
}