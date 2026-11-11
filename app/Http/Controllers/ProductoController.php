<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Services\StockService;
use App\Support\Imagenes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function index()
    {

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

    public function store(ProductoRequest $request, StockService $stock)
    {
        $imagen = $request->hasFile('imagen')
            ? ['imagen' => Imagenes::guardar($request->file('imagen'), 'platos')]
            : [];

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

        $jornada = $producto->jornadaDeHoy();

        return view('admin.productos.edit', compact('producto', 'categorias', 'jornada'));
    }

    public function update(ProductoRequest $request, Producto $producto)
    {
        $imagen = [];
        $imagenAnterior = $producto->imagen;

        if ($request->hasFile('imagen')) {
            $imagen = ['imagen' => Imagenes::guardar($request->file('imagen'), 'platos')];
        }

        $producto->update($request->atributosProducto($imagen));

        if ($imagen && $imagenAnterior) {
            Imagenes::eliminar($imagenAnterior);
        }

        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {

        if ($producto->tieneHistorial()) {
            $producto->forceFill(['activo' => false])->save();

            return redirect()->route('admin.productos.index')->with(
                'success',
                'Producto desactivado (tiene pedidos o ventas asociadas, se conserva el historial).'
            );
        }

        if ($producto->imagen) {
            Imagenes::eliminar($producto->imagen);
        }

        $producto->delete();

        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado correctamente.');
    }
}
