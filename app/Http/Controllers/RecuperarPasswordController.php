<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecuperarPasswordCambioRequest;
use App\Http\Requests\RecuperarPasswordCodigoRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Recuperación de contraseña de clientes en dos pasos: se genera un código de 6
 * dígitos y luego se canjea por una contraseña nueva.
 *
 * El código tiene tres protecciones:
 *   1. Dura 15 minutos.
 *   2. Cada código admite sólo 3 intentos; al superarlos queda invalidado.
 *   3. Se genera con random_int(), que usa el generador aleatorio del sistema.
 *      Con rand() el número sería predecible y se podría adivinar el siguiente.
 *
 * El código se muestra en pantalla porque el proyecto es una demostración sin
 * servicio de correo configurado. En un sistema real se enviaría por SMS o correo.
 */
class RecuperarPasswordController extends Controller
{
    /** Clave de sesión donde vive el código pendiente. */
    private const CLAVE_SESION = 'recuperacion';

    /** Vigencia del código. */
    private const MINUTOS_VIGENCIA = 15;

    /** Intentos permitidos por código generado. */
    private const INTENTOS_MAXIMOS = 3;

    public function formulario()
    {
        return view('auth.recuperar-password');
    }

/**
     * Paso 1: generar el código.
     */
    public function generarCodigo(RecuperarPasswordCodigoRequest $request)
    {
        $user = User::where('email', $request->correoNormalizado())
            ->where('role', 'cliente')
            ->first();

        if (! $user || ! $user->activo) {
            // Mismo mensaje para "no existe" y para "existe pero inactiva": si
            // distinguirlos, el formulario se convierte en un oráculo de
            // enumeración de cuentas.
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

    /**
     * Paso 2: canjear el código y fijar la nueva contraseña.
     */
    public function cambiar(RecuperarPasswordCambioRequest $request)
    {
        $estado = $request->session()->get(self::CLAVE_SESION);

        if (! is_array($estado) || ($estado['email'] ?? null) === null) {
            return redirect()
                ->route('password.formulario')
                ->with('error', 'Primero debes solicitar un código con tu correo.');
        }

        // El código pertenece a un correo; el cliente no puede canjear el código
        // que se generó para otra cuenta.
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

        // El contador se incrementa ANTES de comprobar el código, para que un
        // intento fallido consuma un intento igual que uno exitoso.
        $intentos = (int) ($estado['intentos'] ?? 0) + 1;
        $request->session()->put(self::CLAVE_SESION.'.intentos', $intentos);

        if ($intentos > self::INTENTOS_MAXIMOS) {
            $this->limpiarSesion($request);

            return redirect()
                ->route('password.formulario')
                ->with('error', 'Superaste el número de intentos permitidos. El código fue invalidado.');
        }

        // hash_equals() compara el código entero sin devolver según el tiempo en que
        // tarda, de modo que no permite deducir el código dígito a dígito midiendo
        // el retardo de la respuesta.
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

    /**
     * Cancelar el proceso en curso y limpiar el estado de la sesión.
     */
    public function limpiar(Request $request)
    {
        $this->limpiarSesion($request);

        return redirect()->route('password.formulario');
    }

    /**
     * Borra el estado de recuperación de la sesión.
     */
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
