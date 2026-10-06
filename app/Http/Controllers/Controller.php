<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    /**
     * Método de validación abreviado ($request->validate([...])) disponible en
     * todos los controladores.
     */
    use ValidatesRequests;
}