<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/*
 * P3: el seeder debe crear los usuarios iniciales con su rol correcto
 * (administrador, cajero, cocina y cliente), activos, y leer las
 * contraseñas de variables de entorno, fallando en producción si faltan.
 */

afterEach(function () {
    foreach (['SEED_ADMIN_PASSWORD', 'SEED_CAJERO_PASSWORD', 'SEED_COCINA_PASSWORD', 'SEED_CLIENTE_PASSWORD'] as $variable) {
        putenv($variable);
        unset($_ENV[$variable], $_SERVER[$variable]);
    }
});

test('el seeder crea los cuatro usuarios iniciales con su rol correcto y activos', function () {
    $this->artisan('db:seed')->assertSuccessful();

    $esperados = [
        'admin@donaross.com' => User::ROL_ADMINISTRADOR,
        'personal@donaross.com' => User::ROL_CAJERO,
        'cocina@donaross.com' => User::ROL_COCINA,
        'cliente@correo.com' => User::ROL_CLIENTE,
    ];

    foreach ($esperados as $email => $rol) {
        $usuario = User::where('email', $email)->first();

        expect($usuario)->not->toBeNull()
            ->and($usuario->role)->toBe($rol)
            ->and($usuario->activo)->toBeTrue();
    }
});

test('el seeder conserva las contraseñas por defecto en entorno de pruebas', function () {
    $this->artisan('db:seed')->assertSuccessful();

    $admin = User::where('email', 'admin@donaross.com')->first();
    $cliente = User::where('email', 'cliente@correo.com')->first();

    expect(Hash::check('admin123', $admin->password))->toBeTrue()
        ->and(Hash::check('cliente123', $cliente->password))->toBeTrue();
});

test('en producción el seeder falla con un mensaje claro si falta una variable SEED', function () {
    config(['app.env' => 'production']);
    $this->app->instance('env', 'production');

    expect(fn () => (new DatabaseSeeder())->run())
        ->toThrow(RuntimeException::class, 'SEED_ADMIN_PASSWORD');
});

test('en producción el seeder funciona cuando las variables SEED están definidas', function () {
    config(['app.env' => 'production']);
    $this->app->instance('env', 'production');

    putenv('SEED_ADMIN_PASSWORD=clave-admin-de-prueba');
    putenv('SEED_CAJERO_PASSWORD=clave-cajero-de-prueba');
    putenv('SEED_COCINA_PASSWORD=clave-cocina-de-prueba');
    putenv('SEED_CLIENTE_PASSWORD=clave-cliente-de-prueba');

    (new DatabaseSeeder())->run();

    $admin = User::where('email', 'admin@donaross.com')->first();
    $cocina = User::where('email', 'cocina@donaross.com')->first();

    expect($admin->role)->toBe(User::ROL_ADMINISTRADOR)
        ->and($admin->activo)->toBeTrue()
        ->and(Hash::check('clave-admin-de-prueba', $admin->password))->toBeTrue()
        ->and($cocina->role)->toBe(User::ROL_COCINA)
        ->and(Hash::check('clave-cocina-de-prueba', $cocina->password))->toBeTrue();
});
