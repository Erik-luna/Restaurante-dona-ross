<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilUpdateRequest;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;

/**
 * Panel del cliente: resumen, historial de pedidos y perfil.
 *
 * REGLA DE ESTE CONTROLADOR
 * -------------------------
 * Ninguna acción trabaja con un identificador que venga de la URL, salvo el
 * detalle de un pedido. Todas parten de auth()->user(), así que un cliente solo
 * puede ver sus propios datos. La única excepción es showPedido(), que recibe el
 * {pedido} de la URL y por eso necesita comprobar que le pertenece.
 */
class ClienteDashboardController extends Controller
{
    /**
     * Resumen con los últimos pedidos del cliente.
     *
     * La relación auth()->user()->pedidos() ya filtra por user_id en la consulta,
     * de modo que los pedidos de otros clientes ni siquiera llegan a consultarse.
     */
    public function dashboard()
    {
        $pedidos = auth()->user()->pedidos()->latest()->take(5)->get();
        $carritoCount = array_sum(array_map('intval', session('carrito', [])));

        return view('cliente.dashboard', compact('pedidos', 'carritoCount'));
    }

    /**
     * Historial completo de pedidos del cliente, paginado.
     */
    public function pedidos()
    {
        $pedidos = auth()->user()->pedidos()->with('items.producto')->latest()->paginate(10);

        return view('cliente.pedidos', compact('pedidos'));
    }

    /**
     * Detalle de un pedido.
     *
     * Como el {pedido} viene de la URL, hay que comprobar que sea del cliente
     * conectado antes de mostrarlo. Si no lo es, se corta con un 403.
     */
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

    /**
     * Actualiza nombre, teléfono y dirección del cliente.
     *
     * Si envía contraseña, se exige la actual (lo valida PerfilUpdateRequest) y
     * después se regenera la sesión: es el momento en el que el usuario acaba de
     * demostrar que sabe su contraseña, así que es cuando conviene crear un
     * identificador de sesión nuevo.
     */
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
            // El cast 'hashed' del modelo User la hashea sola, no hace falta Hash::make().
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