<?php

namespace App\Http\Controllers;

use App\Models\User;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::where('role', '!=', User::ROL_CLIENTE)
            ->orderBy('name')
            ->paginate(10);

        return view('admin.usuarios.index', compact('usuarios'));
    }
    public function show(User $usuario)
    {
        abort_unless($usuario->esEmpleado(), 404);

        return view('admin.usuarios.show', compact('usuario'));
    }
}