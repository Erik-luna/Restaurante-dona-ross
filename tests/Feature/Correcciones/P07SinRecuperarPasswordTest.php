<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| P7: se elimina la recuperación de contraseña por formulario
|--------------------------------------------------------------------------
|
| El flujo "recuperar contraseña" generaba un código que quedaba en la
| sesión del propio visitante: nunca se enviaba por correo ni por otro
| canal real, así que cualquiera que conociera el correo de un cliente
| podía restablecer su contraseña. Se eliminan las rutas, el controlador,
| los requests y la vista. La vía válida es que el cliente se comunique
| con el restaurante; el administrador puede restablecerle la contraseña
| desde Admin → Clientes (comportamiento que se documenta abajo).
*/

test('p7 las rutas de recuperacion de contrasena ya no existen', function () {
    foreach (['password.formulario', 'password.codigo', 'password.cambiar', 'password.limpiar'] as $nombre) {
        expect(Route::has($nombre))->toBeFalse();
    }

    $this->get('/recuperar-password')->assertNotFound();
    $this->post('/recuperar-password', ['email' => 'cliente@correo.com'])->assertNotFound();
    $this->post('/recuperar-password/cambiar', [
        'email' => 'cliente@correo.com',
        'codigo' => '123456',
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ])->assertNotFound();
});

test('p7 el login de cliente indica comunicarse con el restaurante', function () {
    $this->get('/login-cliente')
        ->assertOk()
        ->assertSee('Si olvidaste tu contraseña, comunícate con el restaurante.', false)
        ->assertDontSee('/recuperar-password')
        ->assertDontSee('¿Olvidaste tu contraseña?');
});

test('p7 el controlador y los requests de recuperacion fueron eliminados', function () {
    expect(class_exists(\App\Http\Controllers\RecuperarPasswordController::class))->toBeFalse()
        ->and(class_exists(\App\Http\Requests\RecuperarPasswordCodigoRequest::class))->toBeFalse()
        ->and(class_exists(\App\Http\Requests\RecuperarPasswordCambioRequest::class))->toBeFalse();
});

test('p7 reporte el administrador puede restablecer la contrasena de un cliente', function () {
    $admin = User::factory()->create(['role' => User::ROL_ADMINISTRADOR, 'activo' => true]);
    $cliente = User::factory()->create(['role' => User::ROL_CLIENTE, 'activo' => true]);

    $this->actingAs($admin)
        ->put("/admin/clientes/{$cliente->id}", [
            'name' => $cliente->name,
            'email' => $cliente->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
            'telefono' => '71234567',
            'direccion' => 'Calle Falsa 123',
        ])
        ->assertRedirect(route('admin.clientes.index'));

    expect(Hash::check('NuevaClave123', $cliente->refresh()->password))->toBeTrue();
});
