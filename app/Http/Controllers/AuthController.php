<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showClienteLogin()
    {
        return view('auth.login-cliente');
    }

    public function loginCliente(LoginRequest $request)
    {
        $credentials = $request->credenciales();

        $user = User::where('email', $credentials['email'])->first();
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
        return redirect()->intended($user->rutaPanel())
            ->with('success', 'Sesión iniciada correctamente. Bienvenido a Doña Ross.');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

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

    private function regenerarSesion(Request $request, bool $invalidar = false): void
    {
        if ($invalidar) {
            $request->session()->invalidate();
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }
}