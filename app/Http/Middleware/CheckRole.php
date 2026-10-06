<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Comprueba que el usuario autenticado tenga uno de los roles indicados.
 *
 * Se usa así en las rutas:  ->middleware('role:cocina,administrador')
 *
 * Si el visitante no ha iniciado sesión lo manda al formulario de acceso. Si ha
 * iniciado sesión pero su rol no está en la lista, se corta con un 403.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Sin sesión no hay rol que comprobar: al formulario de acceso.
        if (! $request->user()) {
            return redirect()->route('login.cliente')
                ->with('error', 'Debes iniciar sesión.');
        }

        // Una cuenta desactivada por el administrador no puede operar.
        if ($request->user()->activo === false) {
            auth()->logout();

            return redirect()->route('home')->with('error', 'Tu cuenta está desactivada.');
        }

        // Laravel entrega los roles como un único texto "cocina,administrador", así
        // que hay que separarlo para poder compararlo con el del usuario.
        $rolesPermitidos = array_map('trim', explode(',', implode(',', $roles)));

        if (! in_array($request->user()->role, $rolesPermitidos, true)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}