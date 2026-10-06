<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogoFilterRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Support\Texto;

/**
 * PÃ¡ginas pÃºblicas del restaurante (puntos 1 y 6).
 *
 * Traza del endpoint mÃ¡s expuesto de la web:
 *
 *   Fuente    -> GET /catalogo?buscar=...&categoria=...
 *                (no requiere autenticaciÃ³n; se invoca desde la URL, desde un
 *                enlace de correo o desde un crawler)
 *  _controlador-> CatalogoFilterRequest
 *  _sumidero  -> Producto::where(... 'like' ...) -> PDO prepared statement
 */
class PublicController extends Controller
{
    public function index()
    {
        // Se pide la disponibilidad de hoy como columna calculada. El filtro de
        // agotados NO se aplica aquÃ­: un plato sin stock sigue estando en la carta,
        // marcado como agotado. Ocultarlo dejarÃ­a al cliente pensando que el
        // restaurante no lo tiene.
        $destacados = Producto::where('activo', true)
            ->where('destacado', true)
            ->select('productos.*')
            ->selectRaw(Producto::EXPRESION_DISPONIBLE.' as disponible', Producto::rangoDeFecha())
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

    /**
     * CatÃ¡logo pÃºblico con bÃºsqueda.
     *
     * Las dos mitades de la defensa contra la inyecciÃ³n de comodines (punto 6):
     *
     *  - `CatalogoFilterRequest` acota el tÃ©rmino a 100 caracteres y comprueba
     *    que la categorÃ­a exista. Si llegara hasta aquÃ­ un array en lugar de una
     *    cadena, se devolverÃ­a un 422 limpio en lugar de un error 500.
     *
     *  - `Texto::likeSeguro()` neutraliza `%`, `_` y `\`. Sin esto, `?buscar=%`
     *    devolverÃ­a el catÃ¡logo completo, y un patrÃ³n de comodines anidados
     *    (`?buscar=%%%%%%...`) es una forma barata de saturar la CPU del
     *    servidor de base de datos.
     *
     * `withQueryString()` conserva los filtros en la paginaciÃ³n; los valores
     * viajan a la plantilla y se imprimen con `{{ }}`, es decir, escapados.
     */
    public function catalogo(CatalogoFilterRequest $request)
    {
        // Igual que en la portada, el catÃ¡logo lista tambiÃ©n los agotados para
        // marcarlos, en vez de esconderlos.
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

        $productos = $query->orderBy('nombre')->paginate(9)->withQueryString();
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
