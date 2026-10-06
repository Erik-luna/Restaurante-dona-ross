<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad básicas de la respuesta HTTP.
 *
 * Son cuatro cabeceras estándar que explican por sí mismas qué protegen:
 *
 *  - X-Frame-Options: SAMEORIGIN -> impide que otro sitio incruste esta web en un
 *    `<iframe>` (clickjacking: hacer que el usuario haga clic sin darse cuenta).
 *  - X-Content-Type-Options: nosniff -> impide que el navegador adivine el tipo de
 *    un archivo y ejecute como HTML un archivo que no lo es.
 *  - Referrer-Policy -> evita que al saltar a otra web se leaks la URL del pedido
 *    que el cliente estaba viendo.
 *  - Cache-Control: no-store en las páginas con datos de sesión, para que el botón
 *    "atrás" del navegador no reponga en pantalla la página de pedidos de otro
 *    usuario en un equipo compartido.
 *
 * No se usa una CSP con `nonce`: exigiría generar un valor aleatorio por respuesta
 * y propagarlo a todas las plantillas, y las más de cien reglas `style="..."` de
 * este proyecto obligarían además a `unsafe-inline` en estilos. El escapado de Blade
 * (`{{ }}`) es la protección real contra XSS en este sistema.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Solo las páginas privadas (panel, cliente, caja) se marcan como
        // "no cachear". La portada y el catálogo son públicos y sí conviene cachearlos.
        if ($request->is('cliente/*', 'admin/*', 'personal/*', 'cocina/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        }

        return $response;
    }
}