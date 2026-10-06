<?php

namespace App\Http\Requests;

use App\Support\Texto;

/**
 * Inicio de sesión (web).
 *
 * PUNTOS DE CONTROL APLICADOS
 * ---------------------------
 * 1. Validación de servidor: el `required` del HTML es una ayuda visual, no una
 *    garantía. Quien llama al endpoint por curl ignora por completo el atributo.
 * 2. Techo de 128 caracteres en la contraseña: un campo `maxlength` en el navegador
 *    tampoco frena a un cliente scripted, y un `password` de 10 MB es un vector de
 *    denegación de servicio (el hash bcryptRecorta a 72 bytes, pero el trabajo de
 *    leer y tragar los 10 MB ocurre igualmente).
 * 3. Formato de correo estricto: un correo malformado no debe llegar a la consulta
 *    ni, sobre todo, generar una respuesta de error distinta a la de "usuario no
 *    existe", que sería un oráculo de enumeración de cuentas.
 * 4. Tope de 255 en el correo, igual que la columna `users.email`.
 */
class LoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    protected function mensajesPropios(): array
    {
        return [
            'email.required' => 'Debes ingresar tu correo electrónico.',
            'email.email' => 'El formato del correo es inválido.',
            'password.required' => 'Debes ingresar tu contraseña.',
        ];
    }

    /**
     * Credenciales listas para `Auth::attempt()`, con el correo normalizado.
     *
     * Normalizar aquí (y no sólo en el limitador de intentos) es lo que hace que
     * la autenticación y el bloqueo anti-fuerza-bruta hablen del mismo usuario: si
     * el login comparara `A@x.com` contra un almacén que guarda `a@x.com`, el
     * usuario no podría entrar nunca, y si el limitador no normalizara, el
     * bloqueo se podría evadir alternando mayúsculas.
     *
     * @return array<string, string>
     */
    public function credenciales(): array
    {
        return [
            'email' => Texto::normalizarEmail($this->string('email')->toString()),
            'password' => (string) $this->input('password'),
        ];
    }

    public function recordar(): bool
    {
        return $this->boolean('remember');
    }
}
