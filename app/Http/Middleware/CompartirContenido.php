<?php

namespace App\Http\Middleware;

use App\Models\Contenido;
use App\Models\Horario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Comparte con todas las vistas los datos del contenido del sitio.
 *
 * Se hace en un middleware y no en un view composer por dos motivos concretos:
 *
 *  - Las vistas hijas se renderizan ANTES que el layout. Un compositor enganchado a
 *    `layouts.app` llega tarde: la portada, que hereda de ese layout, se renderiza
 *    primero y se encontraría con `$horarioHoy` sin definir.
 *
 *  - Un middleware se ejecuta una vez por petición. Un compositor sobre todas las
 *    vistas ejecutaría las mismas consultas una vez por cada vista de la página.
 *
 * Tampoco se cachea el resultado en una variable estática: esa sobrevive entre
 * peticiones, así que en las pruebas un teléfono guardado en un test aparecería en el
 * siguiente. Aquí cada petición lee lo que hay, y si el administrador acaba de
 * guardar algo, se ve en la respuesta que vuelve.
 */
class CompartirContenido
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share([
            'horarioHoy' => Horario::deHoy(),
            'horariosAgrupados' => Horario::agrupados(),
            'contenido' => Contenido::valores(array_keys(Contenido::EDITABLES)),
            'telefonoEnlace' => Contenido::telefonoEnlazable(),
            'whatsappEnlace' => Contenido::whatsappEnlazable(),
        ]);

        return $next($request);
    }
}