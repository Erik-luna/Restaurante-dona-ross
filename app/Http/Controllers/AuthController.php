<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Inicio y cierre de sesión, y registro de clientes.
 *
 * Hay dos formularios de acceso: uno para clientes y otro para el personal del
 * restaurante. Los tres roles del personal (administrador, cajero y cocina) entran
 * por el mismo formulario y, una vez dentro, cada uno va a su propio panel.
 */
class AuthController extends Controller
{
    public function showClienteLogin()
    {
        return view('auth.login-cliente');
    }

    /**
     * Inicio de sesión de clientes.
     *
     * Primero se comprueba que la cuenta exista y esté activa, y después la
     * contraseña. Si el login falla se deja constancia en el log (sin la
     * contraseña) para poder detectar intentos de acceso no autorizados.
     */
    public function loginCliente(LoginRequest $request)
    {
        $credentials = $request->credenciales();

        $user = User::where('email', $credentials['email'])->first();

        // Un empleado que teclee su correo en el formulario de clientes no debe
        // encontrar su cuenta ahí: cada rol entra por su panel.
        if ($user && ! $user->esCliente()) {
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta no es de cliente. Usa el acceso del personal.',
            ]);
        }

        if ($user && ! $user->activo) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Por favor contacta al restaurante Doña Ross.',
            ]);
        }

        $recordar = $request->recordar();

        if (! Auth::attempt($credentials, $recordar)) {
            Log::notice('Intento de inicio de sesión fallido.', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas. Verifica tu correo y contraseña.',
            ]);
        }

        $this->regenerarSesion($request);

        return redirect()->intended(route('cliente.dashboard'))
            ->with('success', 'Sesión iniciada correctamente. Bienvenido a Doña Ross.');
    }

    public function showAdminLogin()
    {
        return view('auth.login-admin');
    }

    /**
     * Inicio de sesión del personal (administrador, cajero y cocina).
     *
     * El rol se comprueba antes que la contraseña: si alguien teclea el correo de
     * un cliente en el formulario del personal, recibe "Acceso denegado" sin que
     * se llegue a comprobar la contraseña.
     */
    public function loginAdmin(LoginRequest $request)
    {
        $credentials = $request->credenciales();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->esEmpleado()) {
            throw ValidationException::withMessages([
                'email' => 'Acceso denegado. Solo personal autorizado.',
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta se encuentra inactiva.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->recordar())) {
            Log::warning('Intento de acceso del panel fallido.', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas.',
            ]);
        }

        $this->regenerarSesion($request);

        // Cada rol entra a su panel: el administrador al suyo, el cajero al del
        // salón y la cocina al de pedidos.
        return redirect()->intended($user->rutaPanel())
            ->with('success', 'Sesión iniciada correctamente. Bienvenido a Doña Ross.');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Registro público de clientes.
     *
     * El rol NO viene del formulario: lo fija User::registrarCliente(). Por eso el
     * formulario ni siquiera tiene un campo de rol; alguien que añada "rol=admin"
     * a la petición no consigue nada.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::registrarCliente($request->datosCliente());

        Auth::login($user);
        $this->regenerarSesion($request);

        return redirect()
            ->route('cliente.dashboard')
            ->with('success', '¡Registro exitoso! Bienvenido al restaurante Doña Ross.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $this->regenerarSesion($request, invalidar: true);

        return redirect()->route('home')->with('success', 'Sesión cerrada correctamente.');
    }

    /**
     * Crea un identificador de sesión nuevo.
     *
     * Se hace después de iniciar o cerrar sesión para evitar el session fixation:
     * si alguien suelto un identificador de sesión conocido en el navegador de la
     * víctima antes de que entre, sin cambiarlo seguiría autenticada al entrar.
     */
    private function regenerarSesion(Request $request, bool $invalidar = false): void
    {
        if ($invalidar) {
            $request->session()->invalidate();
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }
}