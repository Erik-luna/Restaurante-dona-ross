<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteStoreRequest;
use App\Http\Requests\ClienteUpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de la cartera de clientes.
 *
 * Es una sección del panel del administrador: el cajero elige clientes al
 * registrar una venta, pero no da de alta ni modifica sus datos.
 */
class ClienteController extends Controller
{
    public function index()
    {
        $clientes = User::where('role', User::ROL_CLIENTE)->latest()->paginate(10);

        return view('admin.clientes.index', compact('clientes'));
    }

    /**
     * Visualización de la información de registro: el administrador puede
     * consultarla pero no editarla.
     */
    public function show(User $cliente)
    {
        abort_unless($cliente->esCliente(), 404);

        return view('admin.clientes.show', compact('cliente'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

    /**
     * Alta de cliente desde el panel.
     *
     * El alta pasa por `User::registrarCliente()`, no por `User::create()`, porque
     * `role` y `activo` se retiraron de `$fillable`. Con un `create()` normal estas
     * dos columnas se descartarían en silencio y el usuario se crearía con un rol
     * `NULL`: sin rol no pasa ningún `CheckRole`, así que la cuenta existiría pero
     * no podría entrar en ninguna sección. Peor: un fallo silencioso de
     * asignación masiva es exactamente el tipo de error que nadie detecta hasta
     * que hay un incidente.
     */
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

            // Un cambio de contraseña cierra las sesiones abiertas de ese cliente:
            // es el momento exacto en que una sesión ajena debe morir.
            $this->cerrarSesiones($cliente);
        }

        $cliente->save();

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Baja lógica del cliente.
     *
     * Se desactiva en lugar de borrarse: sus pedidos son evidencia contable y
     * deben seguir consultables. Un borrado físico dejaría pedidos huérfanos y,
     * además, liberaría el correo para que alguien lo volviera a registrar,
     * heredando el historial de una cuenta ajena.
     */
    public function destroy(User $cliente)
    {
        abort_unless($cliente->esCliente(), 404);

        $cliente->forceFill(['activo' => false])->save();
        $this->cerrarSesiones($cliente);

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente dado de baja correctamente.');
    }

    /**
     * Invalida las sesiones abiertas de un cliente.
     *
     * Cada sesión abierta se guarda en la tabla `sessions` con el identificador
     * del usuario. Al borrarlas, cualquier pestaña que el cliente tuviera abierta
     * deja de servir páginas y tiene que volver a iniciar sesión con la
     * contraseña nueva.
     */
    private function cerrarSesiones(User $cliente): void
    {
        DB::table('sessions')->where('user_id', $cliente->id)->delete();
    }
}
