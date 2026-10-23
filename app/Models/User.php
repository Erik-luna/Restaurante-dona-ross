<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    public const ROL_ADMINISTRADOR = 'administrador';
    public const ROL_CAJERO = 'cajero';
    public const ROL_COCINA = 'cocina';
    public const ROL_CLIENTE = 'cliente';
    public const ROLES = [
        self::ROL_ADMINISTRADOR,
        self::ROL_CAJERO,
        self::ROL_COCINA,
        self::ROL_CLIENTE,
    ];
    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'direccion',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }
    public static function registrarCliente(array $datos): self
    {
        return static::query()->forceCreate([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'role' => self::ROL_CLIENTE,
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'activo' => true,
        ]);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function esAdministrador(): bool
    {
        return $this->role === self::ROL_ADMINISTRADOR;
    }

    public function esCajero(): bool
    {
        return $this->role === self::ROL_CAJERO;
    }

    public function esCocina(): bool
    {
        return $this->role === self::ROL_COCINA;
    }

    public function esCliente(): bool
    {
        return $this->role === self::ROL_CLIENTE;
    }
    public function esEmpleado(): bool
    {
        return in_array($this->role, [
            self::ROL_ADMINISTRADOR,
            self::ROL_CAJERO,
            self::ROL_COCINA,
        ], true);
    }
    public function panel(): string
    {
        return match ($this->role) {
            self::ROL_ADMINISTRADOR => 'admin',
            self::ROL_CAJERO => 'cajero',
            self::ROL_COCINA => 'cocina',
            default => 'cliente',
        };
    }
    public function rutaPanel(): string
    {
        return route($this->panel().'.dashboard');
    }
}