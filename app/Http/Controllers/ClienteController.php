<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteStoreRequest;
use App\Http\Requests\ClienteUpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = User::where('role', User::ROL_CLIENTE)->latest()->paginate(10);

        return view('admin.clientes.index', compact('clientes'));
    }

    public function show(User $cliente)
    {
        abort_unless($cliente->esCliente(), 404);

        return view('admin.clientes.show', compact('cliente'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

    public function store(ClienteStoreRequest $request)
    {
        User::registrarCliente($request->datosCliente());

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function edit(User $cliente)
    {
        abort_unless($cliente->esCliente(), 404);

        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(ClienteUpdateRequest $request, User $cliente)
    {
        abort_unless($cliente->esCliente(), 404);

        $cliente->forceFill([
            'name' => $request->validated('name'),
            'email' => $request->correoNormalizado(),
            'telefono' => $request->validated('telefono') ?: null,
            'direccion' => $request->validated('direccion') ?: null,
        ]);

        if (($activo = $request->activo()) !== null) {
            $cliente->activo = $activo;
        }

        if ($request->cambiaPassword()) {
            $cliente->password = $request->validated('password');

            $this->cerrarSesiones($cliente);
        }

        $cliente->save();

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(User $cliente)
    {
        abort_unless($cliente->esCliente(), 404);

        $cliente->forceFill(['activo' => false])->save();
        $this->cerrarSesiones($cliente);

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente dado de baja correctamente.');
    }

    private function cerrarSesiones(User $cliente): void
    {
        DB::table('sessions')->where('user_id', $cliente->id)->delete();
    }
}
