<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAjusteRequestWeb;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        $productos = Producto::orderBy('nombre')
            ->select('productos.*')
            ->selectRaw(Producto::EXPRESION_DISPONIBLE.' as disponible', Producto::rangoDeFecha())
            ->addSelect(['cargado' => $this->columnaDeJornada('inicial')])
            ->addSelect(['reposto' => $this->columnaDeJornada('entradas')])
            ->addSelect(['perdido' => $this->columnaDeJornada('mermas')])
            ->addSelect(['vendido' => $this->columnaDeJornada('consumidos')])
            ->get();

        return view('stock.index', compact('productos'));
    }
    private function columnaDeJornada(string $columna): Builder
    {
        return StockJornada::select($columna)
            ->whereColumn('producto_id', 'productos.id')
            ->whereDate('fecha', now()->toDateString())
            ->limit(1);
    }
    public function update(StockAjusteRequestWeb $request, Producto $producto, StockService $stock)
    {
        $accion = $request->validated('accion');
        $cantidad = (int) $request->validated('cantidad');
        $motivo = $request->validated('motivo');
        $fecha = $request->fechaAjuste();

        $antes = 0;
        $despues = 0;

        DB::transaction(function () use ($stock, $producto, $accion, $cantidad, $motivo, $fecha, $request, &$antes, &$despues) {
            $antes = $stock->jornadaDe($producto, $fecha)->disponible();
            $stock->aplicar($producto, $accion, $cantidad, $motivo, $fecha, $request->user()->id);
            $despues = $stock->jornadaDe($producto, $fecha)->disponible();
        });

        return redirect()->route($this->rutaStock())->with(
            'success',
            sprintf(
                'Stock de "%s" del %s: %d → %d (%+d). %s',
                $producto->nombre,
                $fecha === now()->toDateString() ? 'día de hoy' : $fecha,
                $antes,
                $despues,
                $despues - $antes,
                $this->explicacionDe($accion, $cantidad)
            )
        );
    }
    private function explicacionDe(string $accion, int $cantidad): string
    {
        return match ($accion) {
            StockJornada::ACCION_CARGAR => sprintf('Se cargaron %d porciones como base del día.', $cantidad),
            StockJornada::ACCION_REPONER => sprintf('Se repusieron %d porciones.', $cantidad),
            StockJornada::ACCION_MERMA => sprintf('Se registraron %d porciones de merma.', $cantidad),
        };
    }
    private function rutaStock(): string
    {
        return auth()->user()->esAdministrador() ? 'admin.stock.index' : 'cocina.stock.index';
    }
}