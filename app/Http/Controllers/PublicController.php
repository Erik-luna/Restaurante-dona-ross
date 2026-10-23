<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogoFilterRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Support\Texto;

class PublicController extends Controller
{
    public function index()
    {

        $destacados = Producto::where('activo', true)
            ->where('destacado', true)
            ->select('productos.*')
            ->selectRaw(Producto::EXPRESION_DISPONIBLE.' as disponible', Producto::rangoDeFecha())
            ->orderBy('id', 'asc')
            ->take(3)
            ->get();

        $promociones = Promocion::where('activo', true)
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
            })
            ->take(2)
            ->get();

        return view('home', compact('destacados', 'promociones'));
    }

    public function catalogo(CatalogoFilterRequest $request)
    {
        $query = Producto::with('categoria')
            ->where('activo', true)
            ->select('productos.*')
            ->selectRaw(Producto::EXPRESION_DISPONIBLE.' as disponible', Producto::rangoDeFecha());

        if ($categoria = $request->validated('categoria')) {
            $query->where('categoria_id', (int) $categoria);
        }

        if ($termino = $request->termino()) {
            $query->where('nombre', 'like', Texto::likeSeguro($termino));
        }

        $productos = $query->orderBy('id', 'asc')->paginate(9)->withQueryString();
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        return view('catalogo', compact('productos', 'categorias'));
    }

    public function promociones()
    {
        $promociones = Promocion::where('activo', true)
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
            })
            ->get();

        return view('promociones', compact('promociones'));
    }
}
