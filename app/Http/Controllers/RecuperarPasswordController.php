<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecuperarPasswordCambioRequest;
use App\Http\Requests\RecuperarPasswordCodigoRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecuperarPasswordController extends Controller
{
    private const CLAVE_SESION = 'recuperacion';
    private const MINUTOS_VIGENCIA = 15;
    private const INTENTOS_MAXIMOS = 3;
    public function formulario()
    {
        return view('auth.recuperar-password');
    }
    public function generarCodigo(RecuperarPasswordCodigoRequest $request)
    {
        $user = User::where('email', $request->correoNormalizado())
            ->where('role', 'cliente')
            ->first();

        if (! $user || ! $user->activo) {
            throw ValidationException::withMessages([
                'email' => 'No existe una cuenta de cliente activa asociada a ese correo.',
            ]);
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put(self::CLAVE_SESION, [
            'email' => $user->email,
            'codigo' => $codigo,
            'expira' => now()->addMinutes(self::MINUTOS_VIGENCIA)->getTimestamp(),
            'intentos' => 0,
        ]);

        Log::info('Código de recuperación generado.', ['user_id' => $user->id]);

        return back()->with(
            'success',
            'Código de verificación generado con éxito. Tiene una vigencia de 15 minutos.'
        );
    }
    public function cambiar(RecuperarPasswordCambioRequest $request)
    {
        $estado = $request->session()->get(self::CLAVE_SESION);

        if (! is_array($estado) || ($estado['email'] ?? null) === null) {
            return redirect()
                ->route('password.formulario')
                ->with('error', 'Primero debes solicitar un código con tu correo.');
        }
        if (! hash_equals((string) $estado['email'], $request->correoNormalizado())) {
            $this->limpiarSesion($request);

            return redirect()
                ->route('password.formulario')
                ->with('error', 'La solicitud no coincide con el código generado. Inicia de nuevo.');
        }

        if (now()->getTimestamp() > (int) $estado['expira']) {
            $this->limpiarSesion($request);

            return redirect()
                ->route('password.formulario')
                ->with('error', 'El código ha expirado. Por tu seguridad, solicita uno nuevo.');
        }
        $intentos = (int) ($estado['intentos'] ?? 0) + 1;
        $request->session()->put(self::CLAVE_SESION.'.intentos', $intentos);

        if ($intentos > self::INTENTOS_MAXIMOS) {
            $this->limpiarSesion($request);

            return redirect()
                ->route('password.formulario')
                ->with('error', 'Superaste el número de intentos permitidos. El código fue invalidado.');
        }
        $codigoValido = hash_equals((string) $estado['codigo'], $request->codigo());

        if (! $codigoValido) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'codigo' => "El código de verificación es incorrecto. Intento {$intentos} de "
                        .self::INTENTOS_MAXIMOS.'.',
                ]);
        }

        $user = User::where('email', $request->correoNormalizado())
            ->where('role', 'cliente')
            ->first();

        if (! $user || ! $user->activo) {
            $this->limpiarSesion($request);

            return redirect()
                ->route('password.formulario')
                ->with('error', 'La cuenta ya no está disponible para este cambio.');
        }

        $user->password = $request->validated('password');
        $user->save();

        $this->limpiarSesion($request);
        $request->session()->regenerate();

        Log::info('Contraseña de cliente restablecida.', ['user_id' => $user->id]);

        return redirect()
            ->route('login.cliente')
            ->with('success', 'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.');
    }
    public function limpiar(Request $request)
    {
        $this->limpiarSesion($request);

        return redirect()->route('password.formulario');
    }
    private function limpiarSesion(Request $request): void
    {
        $request->session()->forget([
            self::CLAVE_SESION,
            self::CLAVE_SESION.'.email',
            self::CLAVE_SESION.'.codigo',
            self::CLAVE_SESION.'.expira',
            self::CLAVE_SESION.'.intentos',
        ]);
    }
}
