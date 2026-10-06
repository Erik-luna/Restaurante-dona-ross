<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??=  Hash::make('password'),
            'remember_token' => Str::random(10),
            // Por defecto las pruebas usan un cliente. Cada prueba elige el rol
            // que necesita con ->administrador(), ->cajero() o ->cocina().
            'role' => User::ROL_CLIENTE,
            'activo' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Cuenta de administrador.
     */
    public function administrador(): static
    {
        return $this->state(fn () => ['role' => User::ROL_ADMINISTRADOR]);
    }

    /**
     * Cuenta de cajero.
     */
    public function cajero(): static
    {
        return $this->state(fn () => ['role' => User::ROL_CAJERO]);
    }

    /**
     * Cuenta de cocina.
     */
    public function cocina(): static
    {
        return $this->state(fn () => ['role' => User::ROL_COCINA]);
    }

    /**
     * Cuenta desactivada (para probar el bloqueo de cuentas en CheckRole).
     */
    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
