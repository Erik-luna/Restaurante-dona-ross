<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilUpdateRequest;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;

class ClienteDashboardController extends Controller
{
    public function dashboard()
    {
        $pedidos = auth()->user()->pedidos()->latest()->take(5)->get();
        $carritoCount = array_sum(array_map('intval', session('carrito', [])));

        return view('cliente.dashboard', compact('pedidos', 'carritoCount'));
    }

    public function pedidos()
    {
        $pedidos = auth()->user()->pedidos()->with('items.producto')->latest()->paginate(10);

        return view('cliente.pedidos', compact('pedidos'));
    }

    public function showPedido(Pedido $pedido)
    {
        if ($pedido->user_id !== auth()->id()) {
            abort(403, 'Este pedido no pertenece a tu cuenta.');
        }

        $pedido->load('items.producto');

        return view('cliente.pedido-detalle', compact('pedido'));
    }

    public function perfil()
    {
        return view('cliente.perfil', ['user' => auth()->user()]);
    }

    public function updatePerfil(PerfilUpdateRequest $request)
    {
        $user = auth()->user();
        $cambiaPassword = $request->cambiaPassword();

        $user->forceFill([
            'name' => $request->validated('name'),
            'telefono' => $request->validated('telefono') ?: null,
            'direccion' => $request->validated('direccion') ?: null,
        ]);

        if ($cambiaPassword) {
            $user->password = $request->validated('password');

            Log::info('El cliente cambió su contraseña.', ['user_id' => $user->id]);
        }

        $user->save();

        if ($cambiaPassword) {
            $request->session()->regenerate();
        }

        return back()->with('success', $cambiaPassword
            ? 'Perfil y contraseña actualizados correctamente.'
            : 'Perfil actualizado correctamente.');
    }
}