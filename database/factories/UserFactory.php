<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??=  Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => User::ROL_CLIENTE,
            'activo' => true,
        ];
    }
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
    public function administrador(): static
    {
        return $this->state(fn () => ['role' => User::ROL_ADMINISTRADOR]);
    }
    public function cajero(): static
    {
        return $this->state(fn () => ['role' => User::ROL_CAJERO]);
    }
    public function cocina(): static
    {
        return $this->state(fn () => ['role' => User::ROL_COCINA]);
    }
    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
