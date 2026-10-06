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

/**
 * Pruebas de los reportes.
 *
 * Un reporte es una lectura, y por eso lo peligroso no es que se equivoque en un
 * cálculo: es que diga una cifra que cuadra con lo que el día cuadró pero que en
 * realidad mezcla operaciones distintas. Estas pruebas fijan sobre todo las tres
 * reglas que hacen comparables los números: salón y delivery van separados, el
 * delivery cuenta por su fecha de cobro y lo anulado no entra.
 */
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // El reloj del reporte es la única pieza global que tocan estas pruebas. Si
        // se queda congelado en el día de un test, el siguiente calcularía sus
        // expectativas contra una fecha equivocada y fallaría sin explicación.
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Crea una venta de salón en el día indicado. */
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

    /**
     * Crea un pedido de delivery y, si se pide, lo cobra.
     *
     * El `pagado_at` va explícito y no "ahora" a propósito: casi todas las pruebas de
     * reportes necesitan un pedido creado un día y cobrado otro, que es justo el caso
     * donde contar por la fecha equivocada cambia el resultado.
     */
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

        // El dinero del restaurante es del administrador. Cajero, cocina y cliente
        // quedan fuera: ver cuánto se ganó no ayuda a preparar más silpanchos, y el
        // total del día sí le diría al cliente si el local está moviendo dinero.
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

        // Pedido tomado el día 3 y cobrado el día 4. El dinero entró a la caja al
        // cerrarse el 4, así que pertenece al día 4: contarlo por su creación lo
        // metería en el 3, un día cuyo arqueo ya había cerrado sin ese importe.
        Carbon::setTestNow(Carbon::parse('2026-10-03')->setTime(12, 0));
        $this->pedidoCobradoEn('2026-10-04', $producto, 1, 20.00);

        // Y el revés, para que no sea casualidad: cobrado el 3, el 3.
        $this->pedidoCobradoEn('2026-10-03', $producto, 1, 20.00);

        // El reloj vuelve al día 4: el reporte siempre se lee "hasta hoy", y con el
        // reloj parado en el 3 su ventana no incluiría el 4 y la serie ni lo tendría.
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

        // Se entregó la comida pero el cliente todavía no pagó. El plato se consumió
        // del stock, sí, pero en caja no entró nada: contarlo como ingreso mostraría
        // dinero que no existe.
        $this->pedidoCobradoEn(null, $producto, 3);

        $this->assertSame(0.0, app(ReporteService::class)->totales()['delivery']);
    }

    public function test_lo_anulado_no_cuenta(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $producto = $this->crearProducto(50, ['precio' => 35.00]);

        // Una venta anulada y un pedido cancelado quedan fuera del reporte. Si contaran,
        // el módulo parecería haber facturado más de lo que realmente entró.
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

        // Los 14 días llegan todos. Si el gráfico saltara los días sin venta,
        // dibujaría una pendiente de la semana al lunes que parece caída de ventas.
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

        // El 30 y el 1 caen en meses distintos aunque sean días consecutivos: el
        // corte es el mes natural, no una ventana de 30 días rodante.
        $this->assertSame(10.0, $porMes['2026-09']['total']);
        $this->assertSame(50.0, $porMes['2026-10']['total']);
    }

    public function test_el_flujo_de_caja_usa_las_cifras_congeladas_y_omite_las_abiertas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        $cajero = User::factory()->cajero()->create();

        $this->cajaCerrada($cajero, '2026-10-02', 100, 350, 350);   // cuadrada
        $this->cajaCerrada($cajero, '2026-10-03', 100, 400, 380);   // faltaron 20

        // Una caja abierta no tiene arqueo todavía: el dinero se está contando.
        Caja::create([
            'user_id' => $cajero->id,
            'fecha' => today(),
            'monto_inicial' => 100,
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
        ]);

        $cajas = collect(app(ReporteService::class)->flujoDeCaja());

        $this->assertCount(2, $cajas);

        // La más reciente primero.
        $cerrada = $cajas->first();
        $this->assertSame('2026-10-03', $cerrada->fecha->toDateString());
        $this->assertSame(-20.0, (float) $cerrada->diferencia);

        // Se registra una venta de 100 en una caja que ya está cerrada. `resumen()`
        // la tiene en cuenta porque recalcula, pero el reporte debe seguir mostrando
        // el arqueo congelado: el turno terminó y su historia no se reescribe.
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

        // El precio de mañana no reescribe las ventas de hoy.
        $silpancho->update(['precio' => 50.00]);

        $ranking = collect(app(ReporteService::class)->platosMasVendidos())
            ->keyBy(fn ($fila) => $fila['producto']->nombre);

        $this->assertSame(3, $ranking['Silpancho']['unidades']);
        // 2 x 35 + 1 x 35, no 3 x 50: el importe es el dinero que entró de verdad.
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

        // "Muy pedido" gana por unidades aunque el otro plato traiga más dinero: el
        // ranking responde qué se prepara más, no qué deja más.
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

        // El gráfico diario se dibuja siempre, aunque salga plano: son los 14 días
        // del rango, no solo los que tuvieron ventas.
        $this->assertCount(14, app(ReporteService::class)->ventasPorDia());
    }

    public function test_un_nombre_de_plato_con_html_se_muestra_escapado(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04')->setTime(20, 0));

        // El nombre de un plato lo escribe un usuario: puede traer etiquetas. En el
        // reporte va por `{{ }}`, que escapa, así que aparece como texto y no como
        // HTML. Sin esa escapación, un plato llamado así cerraría la tabla y podría
        // inyectar contenido en el panel del administrador.
        //
        // El plato necesita llevar una venta, porque el ranking solo nombra a lo que
        // se vendió: un plato sin movimientos no aparece en la pantalla.
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