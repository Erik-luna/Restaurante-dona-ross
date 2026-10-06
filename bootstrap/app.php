<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CompartirContenido;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias del middleware de roles: ->middleware('role:cocina,administrador')
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // Cabeceras de seguridad de la respuesta. Se aplican al final para poder
        // fijarse sobre la respuesta ya construida por los controladores y las vistas.
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Datos del contenido del sitio (teléfono, dirección, horarios) para todas las
        // vistas, antes de que se renderice ninguna. Va en el grupo web porque las
        // vistas públicas las necesitan y las del panel no.
        $middleware->web(prepend: [
            CompartirContenido::class,
        ]);

        // Un visitante sin sesión que intente entrar al panel vuelve al formulario de
        // acceso, no a la portada.
        $middleware->redirectGuestsTo(fn () => route('login.cliente'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();
    })->create();