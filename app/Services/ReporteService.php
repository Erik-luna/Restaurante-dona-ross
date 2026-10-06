<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Los números del reporte: qué se vendió, cuánto dinero entró y cómo quedó la caja.
 *
 * Vive en un servicio y no en el controlador por una razón concreta: un reporte que
 * calcula sus cifras con PHP a partir de modelos cargados en memoria funciona con
 * cien ventas y se arrastra con cien mil. Aquí cada bloque es una consulta de
 * agregación que hace la base de datos, que es donde están los datos. Al controlador
 * solo le llega el resultado ya reducido.
 *
 * Dos reglas atraviesan todo el servicio, porque son las que hacen que las cifras
 * sean comparables entre sí:
 *
 *  1. Las ventas de salón y los cobros de delivery se cuentan por separado y jamás
 *     se suman a ciegas. Son dinero de dos operaciones distintas: una se paga en el
 *     mostrador al momento, la otra se cobra después de entregado. Si el reporte
 *    _presentara un único "total de ingresos", cualquier descuadre quedaría sin
 *     explicación.
 *
 *  2. El delivery se cuenta por la FECHA DE COBRO (`pagado_at`), no por la de creación.
 *     El dinero entra en la caja cuando se cobra, que es lo mismo que hace
 *     `Caja::resumen()`. Contarlo por creación haría que un pedido pagado el día 1
 *     apareciera en el reporte del día 30 que se cerró, y las ventas de un día no
 *     cuadrarían con el arqueo de ese día.
 */
class ReporteService
{
    /** Días que mira hacia atrás el reporte diario. */
    public const DIAS = 14;

    /** Meses que mira hacia atrás el reporte mensual. */
    public const MESES = 12;

    /** Cajas que lista el flujo de caja. */
    public const CAJAS = 30;

    /** Platos del ranking. */
    public const TOP_PLATOS = 10;

    /**
     * Ventas por día, separadas en salón y delivery.
     *
     * Sale siempre con todos los días del rango, aunque no haya ventas: un gráfico
     * con los días sin datos saltados dibujaría una línea recta que parecería una
     * subida de ingresos cuando en realidad no se vendió nada.
     *
     * @return array<int, array{dia: string, salon: float, delivery: float, total: float}>
     */
    public function ventasPorDia(int $dias = self::DIAS): array
    {
        $inicio = today()->subDays($dias - 1);

        $serie = $this->serieDiaria($inicio, today());

        $filas = [];

        for ($fecha = $inicio->copy(); $fecha->lte(today()); $fecha->addDay()) {
            $clave = $fecha->toDateString();
            $salon = $serie['salon'][$clave] ?? 0.0;
            $delivery = $serie['delivery'][$clave] ?? 0.0;

            $filas[] = [
                'dia' => $clave,
                'salon' => $salon,
                'delivery' => $delivery,
                'total' => $salon + $delivery,
            ];
        }

        return $filas;
    }

    /**
     * Ventas por mes natural, de los últimos N meses incluido el actual.
     *
     * Se agrupa en PHP y no con DATE_FORMAT() porque cada motor de base de datos
     * escribe el mes de otra forma: MySQL usa `DATE_FORMAT`, SQLite usa `strftime`,
     * y el proyecto tiene pruebas sobre SQLite y datos reales en MySQL. La serie
     * diaria ya viene agregada de la base, así que sumar doce meses son agrupados es
     * barato y funciona en los dos motores.
     *
     * @return array<int, array{mes: string, salon: float, delivery: float, total: float}>
     */
    public function ventasPorMes(int $meses = self::MESES): array
    {
        $dias = max(28, $meses * 31);
        $serie = $this->serieDiaria(today()->subDays($dias - 1), today());

        $acumulado = [];

        foreach ($serie['salon'] as $dia => $monto) {
            $mes = substr($dia, 0, 7);

            $acumulado[$mes] ??= ['salon' => 0.0, 'delivery' => 0.0];
            $acumulado[$mes]['salon'] += $monto;
            $acumulado[$mes]['delivery'] += $serie['delivery'][$dia] ?? 0.0;
        }

        // Los meses sin ninguna venta no aparecen en la serie: un mes vacío se ve
        // igual en un gráfico de líneas, y solo estorba en la tabla.
        krsort($acumulado);

        $filas = [];

        foreach ($acumulado as $mes => $totales) {
            $filas[] = [
                'mes' => $mes,
                'salon' => $totales['salon'],
                'delivery' => $totales['delivery'],
                'total' => $totales['salon'] + $totales['delivery'],
            ];
        }

        return array_slice($filas, 0, $meses);
    }

    /**
     * Las tarjetas de arriba: totales de la ventana del reporte diario.
     *
     * @return array{salon: float, delivery: float, total: float, operaciones: int}
     */
    public function totales(int $dias = self::DIAS): array
    {
        $filas = $this->ventasPorDia($dias);

        $salon = array_sum(array_column($filas, 'salon'));
        $delivery = array_sum(array_column($filas, 'delivery'));

        return [
            'salon' => $salon,
            'delivery' => $delivery,
            'total' => $salon + $delivery,
            // "Operaciones" cuenta documentos, no dinero: una venta de salón y un
            // cobro de delivery son dos, aunque sealsy a sumar el dinero.
            'operaciones' => Venta::whereDate('created_at', '>=', today()->subDays($dias - 1))
                ->where('estado', Venta::PAGADA)
                ->count()
                + Pedido::whereNotNull('pagado_at')
                    ->whereDate('pagado_at', '>=', today()->subDays($dias - 1)->startOfDay())
                    ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
                    ->count(),
        ];
    }

    /**
     * Cómo quedó el dinero en cada caja cerrada.
     *
     * Usa las columnas congeladas al cerrar y no recalcula nada, por la misma razón
     * que `Caja::arqueo()`: un cobro de un día anterior no debe poder alterar la
     * historia de un turno que ya terminó.
     *
     * @return \Illuminate\Support\Collection<int, Caja>
     */
    public function flujoDeCaja(int $limite = self::CAJAS)
    {
        return Caja::with('user')
            ->where('estado', Caja::CERRADA)
            ->whereNotNull('total_esperado')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * Los platos más vendidos del histórico.
     *
     * Cuenta salón y delivery por separado y los suma, porque aquí la pregunta es
     * qué plato se prepara más, no de dónde vino el pedido. Se excluyen los pedidos
     * cancelados: la comida se preparó pero no se vendió.
     *
     * El importe se calcula con el precio unitario con el que se vendió, no con el
     * precio actual del producto: un plato que subió de 30 a 35 en enero tiene que
     * seguir valiendo 30 en las ventas de diciembre.
     *
     * @return array<int, array{producto: Producto, unidades: int, importe: float}>
     */
    public function platosMasVendidos(int $limite = self::TOP_PLATOS): array
    {
        $ventas = DB::table('venta_items')
            ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
            ->where('ventas.estado', Venta::PAGADA)
            ->selectRaw('producto_id, SUM(cantidad) as unidades, SUM(cantidad * precio_unitario) as importe')
            ->groupBy('producto_id');

        $pedidos = DB::table('pedido_items')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->where('pedidos.estado', '!=', Pedido::ESTADO_CANCELADO)
            ->selectRaw('producto_id, SUM(cantidad) as unidades, SUM(cantidad * precio_unitario) as importe')
            ->groupBy('producto_id');

        // `UNION ALL` y no `UNION`: las dos consultas no se solapan entre sí, así que
        // el DISTINCT que mete UNION solo costaría tiempo.
        $ranking = DB::query()
            ->fromSub($ventas->unionAll($pedidos), 'movimientos')
            // Con nombre de tabla delante: `unidades` e `importe` son los alias que
            // traen las dos consultas del UNION, y qualificarlos evita depender de
            // que el motor adivine de qué columna sale cada suma.
            ->selectRaw('producto_id, SUM(movimientos.unidades) as unidades, SUM(movimientos.importe) as importe')
            ->groupBy('producto_id')
            ->orderByDesc('unidades')
            ->orderByDesc('importe')
            ->limit($limite)
            ->get();

        $productos = Producto::whereIn('id', $ranking->pluck('producto_id'))->get()->keyBy('id');

        $filas = [];

        foreach ($ranking as $fila) {
            $producto = $productos->get($fila->producto_id);

            // Un producto borrado con historial es raro pero posible; si el ranking lo
            // nombra y no se puede leer, mejor no romper la pantalla entera.
            if ($producto === null) {
                continue;
            }

            $filas[] = [
                'producto' => $producto,
                'unidades' => (int) $fila->unidades,
                'importe' => (float) $fila->importe,
            ];
        }

        return $filas;
    }

    /**
     * La serie diaria cruda, indexada por fecha, que alimentan el gráfico diario y el
     * mensual. Una sola consulta por tipo de operación para todo el reporte.
     *
     * @param  \Illuminate\Support\Carbon  $desde
     * @param  \Illuminate\Support\Carbon  $hasta
     * @return array{salon: array<string, float>, delivery: array<string, float>}
     */
    private function serieDiaria($desde, $hasta): array
    {
        $porDia = static function (string $columna) use ($desde, $hasta) {
            return DB::table('ventas')
                ->where('estado', Venta::PAGADA)
                ->whereDate($columna, '>=', $desde->toDateString())
                ->whereDate($columna, '<=', $hasta->toDateString())
                ->selectRaw('DATE('.$columna.') as dia, SUM(total) as total')
                ->groupBy('dia')
                ->pluck('total', 'dia');
        };

        $salon = $porDia('created_at');

        // El delivery se filtra y se agrupa por `pagado_at`: la fecha en que el
        // dinero entró a la caja. Un pedido entregado sin cobrar no cuenta como
        // ingreso, igual que en el arqueo.
        $delivery = DB::table('pedidos')
            ->whereNotNull('pagado_at')
            ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
            ->whereDate('pagado_at', '>=', $desde->toDateString())
            ->whereDate('pagado_at', '<=', $hasta->toDateString())
            ->selectRaw('DATE(pagado_at) as dia, SUM(total) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $aMapa = static fn ($coleccion) => $coleccion->mapWithKeys(
            fn ($monto, $dia) => [$dia => (float) $monto]
        )->all();

        return [
            'salon' => $aMapa($salon),
            'delivery' => $aMapa($delivery),
        ];
    }
}