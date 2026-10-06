<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** El administrador: acceso total al sistema. */
    public const ROL_ADMINISTRADOR = 'administrador';

    /** El cajero: cobra en el salón y registra las ventas presenciales. */
    public const ROL_CAJERO = 'cajero';

    /** La cocina: recibe los pedidos y controla las porciones disponibles. */
    public const ROL_COCINA = 'cocina';

    /** El cliente: hace pedidos de delivery. */
    public const ROL_CLIENTE = 'cliente';

    /**
     * Roles que entran al panel del restaurante (todo menos el cliente).
     *
     * La columna `role` de la base de datos es un ENUM con estos mismos cuatro
     * valores, así que esta lista y la base de datos no pueden desincronizarse.
     *
     * @var list<string>
     */
    public const ROLES = [
        self::ROL_ADMINISTRADOR,
        self::ROL_CAJERO,
        self::ROL_COCINA,
        self::ROL_CLIENTE,
    ];

    /**
     * Campos que se pueden rellenar desde un formulario.
     *
     * 'role' y 'activo' NO están aquí a propósito: si lo estuvieran, alguien podría
     * añadir "role=administrador" al formulario de registro y convertirse en
     * administrador. El registro público lo fija registrarCliente() y siempre
     * crea un cliente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'direccion',
    ];

    /** Campos que nunca se muestran en las vistas ni en las respuestas JSON. */
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

    /**
     * Crea una cuenta de cliente con el rol 'cliente' ya fijado.
     *
     * @param  array<string, mixed>  $datos  Datos ya validados por su Form Request.
     */
    public static function registrarCliente(array $datos): self
    {
        return static::query()->forceCreate([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'], // el cast 'hashed' la hashea sola
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

    /**
     * Cualquier empleado del restaurante: administrador, cajero o cocina.
     *
     * Los tres comparten el mismo formulario de acceso, así que el login del
     * personal usa este método en lugar de listar los roles uno a uno.
     */
    public function esEmpleado(): bool
    {
        return in_array($this->role, [
            self::ROL_ADMINISTRADOR,
            self::ROL_CAJERO,
            self::ROL_COCINA,
        ], true);
    }

    /**
     * Prefijo de las rutas del panel al que pertenece este usuario.
     *
     * Cada rol tiene su propio panel ('admin', 'cajero', 'cocina', 'cliente') y
     * sus rutas empiezan por ese nombre. Devolverlo desde aquí evita repetir el
     * `if` por rol en cada vista compartida entre paneles: basta con escribir
     * route(auth()->user()->panel().'.pedidos.index').
     */
    public function panel(): string
    {
        return match ($this->role) {
            self::ROL_ADMINISTRADOR => 'admin',
            self::ROL_CAJERO => 'cajero',
            self::ROL_COCINA => 'cocina',
            default => 'cliente',
        };
    }

    /**
     * Panel al que debe ir esta persona después de iniciar sesión.
     *
     * Se decide con el rol y no con la URL que el usuario escribió antes de
     * perder la sesión, para que nadie acabe en un panel que no le corresponde.
     */
    public function rutaPanel(): string
    {
        return route($this->panel().'.dashboard');
    }
}