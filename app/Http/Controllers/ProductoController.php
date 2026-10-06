<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function index()
    {
        // El listado lleva la disponibilidad de hoy como columna calculada, para
        // que el administrador vea si el plato está vendible sin abrir el control de
        // stock en otra pestaña.
        $productos = Producto::with('categoria')
            ->select('productos.*')
            ->selectRaw(Producto::EXPRESION_DISPONIBLE.' as disponible', Producto::rangoDeFecha())
            ->latest()
            ->paginate(10);

        return view('admin.productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = Categoria::activas()->get();

        return view('admin.productos.create', compact('categorias'));
    }

    /**
     * Alta de producto.
     *
     * El nombre del archivo de imagen NUNCA se toma del cliente: `store()`
     * genera un nombre aleatorio en el servidor. Usar el nombre enviado sería lo
     * que permite escribir `../../.env` o sustituir un `.php` del directorio
     * público, y además permitiría subir dos archivos con el mismo nombre y que
     * uno pisara al otro sin aviso.
     */
    public function store(ProductoRequest $request, StockService $stock)
    {
        $imagen = $request->hasFile('imagen')
            ? ['imagen' => $request->file('imagen')->store('productos', 'public')]
            : [];

        // El alta y la carga de porciones de hoy van en la misma transacción: un
        // producto creado sin sus porciones es un plato que el catálogo no puede
        // ofrecer, y esa fila a medias solo se descubriría abriendo el catálogo.
        DB::transaction(function () use ($request, $imagen, $stock) {
            $producto = Producto::create($request->atributosProducto($imagen));

            $stock->aplicar(
                $producto,
                StockJornada::ACCION_CARGAR,
                $request->porcionesIniciales(),
                'Alta del producto en el catálogo.',
                now()->toDateString(),
                $request->user()->id
            );
        });

        return redirect()->route('admin.productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::activas()->get();

        // La carga del día se pasa aparte para que quede claro en la vista que es un
        // dato de hoy y no una propiedad permanente del producto.
        $jornada = $producto->jornadaDeHoy();

        return view('admin.productos.edit', compact('producto', 'categorias', 'jornada'));
    }

    /**
     * Edición de producto.
     *
     * Aquí no se toca el stock del día. Si la ficha pisara `inicial` con el número
     * que se escribe en el formulario, la jornada podría quedar con menos inicial
     * que sus propios consumos y el plato aparecería en negativo. Para mover el
     * stock del día está la pantalla de control de stock, que además lleva cuenta
     * de lo ya vendido.
     */
    public function update(ProductoRequest $request, Producto $producto)
    {
        $imagen = [];

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $imagen = ['imagen' => $request->file('imagen')->store('productos', 'public')];
        }

        $producto->update($request->atributosProducto($imagen));

        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Baja de producto: si tiene historial se desactiva en lugar de borrarse, para
     * conservar los pedidos y las ventas ya registrados.
     */
    public function destroy(Producto $producto)
    {
        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
            $producto->forceFill(['imagen' => null])->saveQuietly();
        }

        // Se mira en las dos tablas: un producto que sólo se vendió en el salón
        // tampoco puede borrarse, porque sus ventas deben seguir siendo legibles.
        if ($producto->tieneHistorial()) {
            $producto->forceFill(['activo' => false])->save();

            return redirect()->route('admin.productos.index')->with(
                'success',
                'Producto desactivado (tiene pedidos o ventas asociadas, se conserva el historial).'
            );
        }

        $producto->delete();

        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado correctamente.');
    }
}
