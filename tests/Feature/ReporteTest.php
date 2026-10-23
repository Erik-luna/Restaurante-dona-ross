<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
    private function ventaEn(string $fecha, Producto $producto, int $cantidad = 1, float $precio = 35.00, string $estado = Venta::PAGADA): Venta
    {
        Carbon::setTestNow(Carbon::parse($fecha)->setTime(12, 30));

        $venta = Venta::create([
            'user_id' => null,
            'estado' => $estado,
            'total' => $precio * $cantidad,
            'metodo_pago' => Venta::PAGO_EFECTIVO,
        ]);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $precio * $cantidad,
        ]);

        return $venta;
    }
    private function pedidoCobradoEn(?string $pagoEn, Producto $producto, int $cantidad = 1, float $precio = 35.00, string $estado = Pedido::ESTADO_ENTREGADO): Pedido
    {
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $pedido = Pedido::create([
            'user_id' => $cliente->id,
            'estado' => $estado,
            'total' => $precio * $cantidad,
            'direccion_entrega' => 'Av. Siempre Viva 742',
            'pagado_at' => $pagoEn === null ? null : Carbon::parse($pagoEn)->setTime(19, 0),
        ]);

        $pedido->items()->create([
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $precio * $cantidad,
        ]);

        return $pedido;
    }

    private function cajaCerrada(User $cajero, string $fecha, float $inicial, float $esperado, float $declarado): Caja
    {
        return Caja::create([
            'user_id' => $cajero->id,
            'fecha' => $fecha,
            'monto_inicial' => $inicial,
            'estado' => Caja::CERRADA,
            'apertura_at' => Carbon::parse($fecha)->setTime(9, 0),
            'cierre_at' => Carbon::parse($fecha)->setTime(21, 0),
            'total_ventas' => $esperado,
            'total_cobros' => 0,
            'total_entradas' => 0,
            'total_salidas' => 0,
            'total_esperado' => $esperado,
            'total_declarado' => $declarado,
            'diferencia' => $declarado - $esperado,
        ]);
    }

    public function test_solo_el_administrador_entra_a_los_reportes(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.reportes.index'))
            ->assertOk()
            ->assertSee('Reportes');
        $this->actingAs(User::factory()->cajero()->create())
            ->get(route('admin.reportes.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->cocina()->create())
            ->get(route('admin.reportes.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROL_CLIENTE]))
            ->get(route('admin.reportes.index'))
            ->assertForbidden();
    }

    public function test_el_reporte_separa_salon_de_delivery(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $producto = $this->crearProducto(50, ['precio' => 35.00]);
        $this->ventaEn('2026-10-04', $producto, 2);
        $this->pedidoCobradoEn('2026-10-04', $producto, 1);

        $totales = app(ReporteService::class)->totales();

        $this->assertSame(70.0, $totales['salon']);
        $this->assertSame(35.0, $totales['delivery']);
        $this->assertSame(105.0, $totales['total']);
        $this->assertSame(2, $totales['operaciones']);
    }

public function test_el_delivery_se_cuenta_por_la_fecha_de_cobro_y_no_por_la_del_pedido(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(23, 0));

        $producto = $this->crearProducto(50, ['precio' => 20.00]);
        Carbon::setTestNow(Carbon::parse('2026-10-03')->setTime(12, 0));
        $this->pedidoCobradoEn('2026-10-04', $producto, 1, 20.00);
        $this->pedidoCobradoEn('2026-10-03', $producto, 1, 20.00);
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(23, 0));
        $porDia = collect(app(ReporteService::class)->ventasPorDia())->keyBy('dia');
        $this->assertSame(20.0, $porDia['2026-10-03']['delivery']);
        $this->assertSame(20.0, $porDia['2026-10-04']['delivery']);
        $this->assertSame(40.0, app(ReporteService::class)->totales()['delivery']);
    }

    public function test_un_pedido_entregado_sin_cobrar_no_es_ingreso(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $producto = $this->crearProducto(50, ['precio' => 35.00]);

        $this->pedidoCobradoEn(null, $producto, 3);

        $this->assertSame(0.0, app(ReporteService::class)->totales()['delivery']);
    }

    public function test_lo_anulado_no_cuenta(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $producto = $this->crearProducto(50, ['precio' => 35.00]);

        $this->ventaEn('2026-10-04', $producto, 2, 35.00, Venta::ANULADA);
        $this->pedidoCobradoEn('2026-10-04', $producto, 2, 35.00, Pedido::ESTADO_CANCELADO);

        $totales = app(ReporteService::class)->totales();

        $this->assertSame(0.0, $totales['salon']);
        $this->assertSame(0.0, $totales['delivery']);
        $this->assertSame(0, $totales['operaciones']);
    }

    public function test_la_serie_diaria_trae_los_dias_sin_ventas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $producto = $this->crearProducto(50, ['precio' => 35.00]);
        $this->ventaEn('2026-10-04', $producto, 1);

        $porDia = collect(app(ReporteService::class)->ventasPorDia())->keyBy('dia');
        $this->assertCount(14, $porDia);
        $this->assertSame('2026-09-21', $porDia->keys()->first());
        $this->assertSame('2026-10-04', $porDia->keys()->last());

        $this->assertSame(0.0, $porDia['2026-10-03']['total']);
        $this->assertSame(35.0, $porDia['2026-10-04']['total']);
    }

    public function test_las_ventas_mensuales_se_agrupan_por_mes_natural(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $producto = $this->crearProducto(50, ['precio' => 10.00]);

        $this->ventaEn('2026-09-30', $producto, 1, 10.00);
        $this->ventaEn('2026-10-01', $producto, 2, 10.00);
        $this->ventaEn('2026-10-03', $producto, 3, 10.00);

        $porMes = collect(app(ReporteService::class)->ventasPorMes())->keyBy('mes');

        $this->assertSame(10.0, $porMes['2026-09']['total']);
        $this->assertSame(50.0, $porMes['2026-10']['total']);
    }

    public function test_el_flujo_de_caja_usa_las_cifras_congeladas_y_omite_las_abiertas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $cajero = User::factory()->cajero()->create();

        $this->cajaCerrada($cajero, '2026-10-02', 100, 350, 350);
        $this->cajaCerrada($cajero, '2026-10-03', 100, 400, 380);
        Caja::create([
            'user_id' => $cajero->id,
            'fecha' => today(),
            'monto_inicial' => 100,
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
        ]);

        $cajas = collect(app(ReporteService::class)->flujoDeCaja());

        $this->assertCount(2, $cajas);
        $cerrada = $cajas->first();
        $this->assertSame('2026-10-03', $cerrada->fecha->toDateString());
        $this->assertSame(-20.0, (float) $cerrada->diferencia);

        $producto = $this->crearProducto(50, ['precio' => 100.00]);
        Carbon::setTestNow(Carbon::parse('2026-10-03')->setTime(15, 0));
        $venta = $this->ventaEn('2026-10-03', $producto, 1, 100.00);
        $venta->update(['caja_id' => $cerrada->id]);

        $this->assertSame(200.0, $cerrada->resumen()['esperado']);

        $reabierta = app(ReporteService::class)->flujoDeCaja()->firstWhere('id', $cerrada->id);
        $this->assertSame(400.0, (float) $reabierta->total_esperado);
        $this->assertSame(-20.0, (float) $reabierta->diferencia);
    }

    public function test_los_platos_mas_vendidos_suman_salon_y_delivery_con_el_precio_historico(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $silpancho = $this->crearProducto(50, ['nombre' => 'Silpancho', 'precio' => 35.00]);
        $jugo = $this->crearProducto(50, ['nombre' => 'Jugo', 'precio' => 8.00]);

        $this->ventaEn('2026-10-04', $silpancho, 2, 35.00);
        $this->pedidoCobradoEn('2026-10-04', $silpancho, 1, 35.00);
        $this->pedidoCobradoEn('2026-10-04', $jugo, 5, 8.00);

        $silpancho->update(['precio' => 50.00]);

        $ranking = collect(app(ReporteService::class)->platosMasVendidos())
            ->keyBy(fn ($fila) => $fila['producto']->nombre);

        $this->assertSame(3, $ranking['Silpancho']['unidades']);
        $this->assertSame(105.0, $ranking['Silpancho']['importe']);
        $this->assertSame(5, $ranking['Jugo']['unidades']);
        $this->assertSame(40.0, $ranking['Jugo']['importe']);
    }

    public function test_el_ranking_ordena_por_unidades_y_ignora_lo_cancelado(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $poco = $this->crearProducto(50, ['nombre' => 'Poco pedido', 'precio' => 50.00]);
        $mucho = $this->crearProducto(50, ['nombre' => 'Muy pedido', 'precio' => 10.00]);

        $this->ventaEn('2026-10-04', $poco, 2, 50.00);
        $this->pedidoCobradoEn('2026-10-04', $mucho, 9, 10.00);
        $this->pedidoCobradoEn('2026-10-04', $mucho, 30, 10.00, Pedido::ESTADO_CANCELADO);

        $ranking = app(ReporteService::class)->platosMasVendidos();
        $this->assertSame('Muy pedido', $ranking[0]['producto']->nombre);
        $this->assertSame(9, $ranking[0]['unidades']);
    }

    public function test_el_reporte_sin_datos_no_rompe_la_pantalla(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.reportes.index'))
            ->assertOk()
            ->assertSee('Todavía no se ha cerrado ninguna caja.')
            ->assertSee('Todavía no se ha vendido nada.')
            ->assertSee('Todavía no hay ventas con las que armar el histórico mensual.');
        $this->assertCount(14, app(ReporteService::class)->ventasPorDia());
    }

    public function test_un_nombre_de_plato_con_html_se_muestra_escapado(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));
        $producto = $this->crearProducto(50, ['nombre' => '</td><script>alert(1)</script>', 'precio' => 35.00]);
        $this->ventaEn('2026-10-04', $producto, 1, 35.00);

        $html = $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.reportes.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;/td&gt;&lt;script&gt;', $html);
    }
}