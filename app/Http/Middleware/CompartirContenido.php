<?php

namespace App\Http\Middleware;

use App\Models\Contenido;
use App\Models\Horario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

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
            'qrPagoUrl' => Contenido::qrPagoUrl(),
        ]);

        return $next($request);
    }
}