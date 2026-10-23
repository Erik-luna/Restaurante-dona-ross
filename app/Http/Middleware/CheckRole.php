<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login.cliente')
                ->with('error', 'Debes iniciar sesión.');
        }
        if ($request->user()->activo === false) {
            auth()->logout();

            return redirect()->route('home')->with('error', 'Tu cuenta está desactivada.');
        }
        $rolesPermitidos = array_map('trim', explode(',', implode(',', $roles)));

        if (! in_array($request->user()->role, $rolesPermitidos, true)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}