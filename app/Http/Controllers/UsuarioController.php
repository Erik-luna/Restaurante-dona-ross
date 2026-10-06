<?php

namespace App\Http\Controllers;

use App\Models\User;

/**
 * Gestión de las cuentas del restaurante (administrador, cajero y cocina).
 *
 * Es una sección del panel del administrador. Aquí solo se consulta la
 * información de cada cuenta; el alta y la edición de clientes se hacen en
 * ClienteController.
 */
class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::where('role', '!=', User::ROL_CLIENTE)
            ->orderBy('name')
            ->paginate(10);

        return view('admin.usuarios.index', compact('usuarios'));
    }

    /**
     * Ficha de una cuenta del restaurante.
     *
     * La comprobación del rol evita que, cambiando el número de la URL, se
     * llegue a la ficha de un cliente: esa información se consulta desde
     * /admin/clientes.
     */
    public function show(User $usuario)
    {
        abort_unless($usuario->esEmpleado(), 404);

        return view('admin.usuarios.show', compact('usuario'));
    }
}