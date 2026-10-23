<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    public const DIAS = 14;
    public const MESES = 12;
    public const CAJAS = 30;
    public const TOP_PLATOS = 10;
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
    public function totales(int $dias = self::DIAS): array
    {
        $filas = $this->ventasPorDia($dias);

        $salon = array_sum(array_column($filas, 'salon'));
        $delivery = array_sum(array_column($filas, 'delivery'));

        return [
            'salon' => $salon,
            'delivery' => $delivery,
            'total' => $salon + $delivery,
            'operaciones' => Venta::whereDate('created_at', '>=', today()->subDays($dias - 1))
                ->where('estado', Venta::PAGADA)
                ->count()
                + Pedido::whereNotNull('pagado_at')
                    ->whereDate('pagado_at', '>=', today()->subDays($dias - 1)->startOfDay())
                    ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
                    ->count(),
        ];
    }
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
        $ranking = DB::query()
            ->fromSub($ventas->unionAll($pedidos), 'movimientos')
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