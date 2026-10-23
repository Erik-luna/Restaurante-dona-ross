<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\StockJornada;
use App\Services\StockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            ? ['imagen' => $this->guardarImagen($request->file('imagen'))]
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
            $imagen = ['imagen' => $this->guardarImagen($request->file('imagen'))];
        }

        $producto->update($request->atributosProducto($imagen));

        if ($imagen && $imagenAnterior) {
            $this->eliminarImagen($imagenAnterior);
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
            $this->eliminarImagen($producto->imagen);
        }

        $producto->delete();

        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    private function guardarImagen(UploadedFile $imagen): string
    {
        $directorio = public_path('img/platos');
        File::ensureDirectoryExists($directorio);

        $nombre = Str::uuid().'.'.$imagen->extension();
        $imagen->move($directorio, $nombre);

        return 'img/platos/'.$nombre;
    }

    private function eliminarImagen(string $imagen): void
    {
        if (str_starts_with($imagen, 'img/platos/')) {
            $nombre = substr($imagen, strlen('img/platos/'));

            if ($nombre !== '' && basename($nombre) === $nombre) {
                $ruta = public_path('img/platos/'.$nombre);

                if (is_file($ruta) && ! unlink($ruta)) {
                    throw new \RuntimeException('No se pudo eliminar la imagen del producto.');
                }
            }

            return;
        }

        if (str_starts_with($imagen, 'productos/')) {
            $nombre = substr($imagen, strlen('productos/'));

            if ($nombre !== '' && basename($nombre) === $nombre) {
                Storage::disk('public')->delete($imagen);
            }
        }
    }
}
