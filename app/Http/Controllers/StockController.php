<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAjusteRequestWeb;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Control de las porciones disponibles de cada plato.
 *
 * Lo usan el administrador y la cocina: el administrador carga las porciones al
 * abrir el día y la cocina las ajusta durante el servicio cuando un plato se agota
 * o llega del proveedor. Es el mismo formulario y las mismas reglas para los dos.
 *
 * La pantalla muestra el detalle de la jornada de hoy, no un número suelto: cuánto
 * se preparó, cuánto entró, cuánto se perdió, cuánto se vendió y cuánto queda. Un
 * número suelto obliga a reconstruir esa historia a mano justo cuando algo no
 * cuadra, que es cuando más falta hace tenerla a la vista.
 */
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

    /**
     * Subconsulta con una de las columnas acumuladas de la jornada de hoy.
     *
     * Se devuelve como Builder, y no como texto SQL, porque la columna (`inicial`,
     * `entradas`...) es un nombre real de la tabla: el motor debe entrecomillarla y
     * eso lo hace él mejor que una concatenación a mano.
     */
    private function columnaDeJornada(string $columna): Builder
    {
        return StockJornada::select($columna)
            ->whereColumn('producto_id', 'productos.id')
            ->whereDate('fecha', now()->toDateString())
            ->limit(1);
    }

    /**
     * Ajuste del stock de un producto para un día.
     *
     * El cambio se suma a una de las columnas acumuladas de la jornada (inicial,
     * entradas o mermas) en lugar de pisar una cifra absoluta. En los tres casos se
     * informa cuánto había y cuánto queda, para que quien ajusta vea de inmediato el
     * efecto de lo que acaba de hacer.
     */
    public function update(StockAjusteRequestWeb $request, Producto $producto, StockService $stock)
    {
        $accion = $request->validated('accion');
        $cantidad = (int) $request->validated('cantidad');
        $motivo = $request->validated('motivo');
        $fecha = $request->fechaAjuste();

        $antes = 0;
        $despues = 0;

        DB::transaction(function () use ($stock, $producto, $accion, $cantidad, $motivo, $fecha, $request, &$antes, &$despues) {
            // Si el ajuste es de una fecha anterior, la jornada a tocar es la de ese
            // día y no la de hoy. Corregir el stock de ayer es una tarea real: sale a
            // la luz al revisar el arqueo de la mañana.
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

    /** La frase que deja por escrito qué se hizo, además de la cifra final. */
    private function explicacionDe(string $accion, int $cantidad): string
    {
        return match ($accion) {
            StockJornada::ACCION_CARGAR => sprintf('Se cargaron %d porciones como base del día.', $cantidad),
            StockJornada::ACCION_REPONER => sprintf('Se repusieron %d porciones.', $cantidad),
            StockJornada::ACCION_MERMA => sprintf('Se registraron %d porciones de merma.', $cantidad),
        };
    }

    /**
     * Al terminar, cada rol vuelve a su propio listado de stock.
     */
    private function rutaStock(): string
    {
        return auth()->user()->esAdministrador() ? 'admin.stock.index' : 'cocina.stock.index';
    }
}