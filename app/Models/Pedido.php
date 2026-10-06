<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido de delivery: lo que un cliente pide desde el catálogo y se le lleva a su
 * casa.
 *
 * Esta tabla es SÓLO de delivery. Lo que se consume en el salón se guarda en
 * Venta (ver model Venta): antes las dos cosas convivían en `pedidos` con una
 * columna `tipo`, y había que acordarse de filtrar por ella en cada consulta.
 */
class Pedido extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_PREPARANDO = 'preparando';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_ENTREGADO = 'entregado';
    public const ESTADO_CANCELADO = 'cancelado';

    /** Estados válidos de un pedido. */
    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_CONFIRMADO,
        self::ESTADO_PREPARANDO,
        self::ESTADO_ENVIADO,
        self::ESTADO_ENTREGADO,
        self::ESTADO_CANCELADO,
    ];

    public const PAGO_EFECTIVO = 'efectivo';
    public const PAGO_QR = 'qr';
    public const PAGO_TARJETA = 'tarjeta';

    /** Métodos de pago con los que se puede cobrar un pedido. */
    public const METODOS_PAGO = [
        self::PAGO_EFECTIVO,
        self::PAGO_QR,
        self::PAGO_TARJETA,
    ];

    protected $fillable = [
        'user_id',
        'estado',
        'total',
        'direccion_entrega',
        'telefono_contacto',
        'notas',
        'atendido_por',
        'caja_id',
        'metodo_pago',
        'monto_recibido',
        'cambio',
        'pagado_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'monto_recibido' => 'decimal:2',
            'cambio' => 'decimal:2',
            'pagado_at' => 'datetime',
        ];
    }

    /** Cliente que hizo el pedido. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Persona que gestionó el pedido. */
    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    /**
     * Caja que cobró el pedido. Nulo mientras siga pendiente de cobro.
     *
     * El dinero del pedido entra en una caja, pero la cocina que lo preparó no
     * tiene por qué ser la misma persona que lo cobró: por eso la caja se guarda
     * aparte de `atendido_por`.
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /** ¿Ya entró el dinero de este pedido a alguna caja? */
    public function estaCobrado(): bool
    {
        return $this->pagado_at !== null;
    }

    /**
     * ¿Este pedido sigue debiendo dinero?
     *
     * Un pedido cancelado no debe dinero: no se cobró y no se cobrará. Uno
     * entregado sin cobrar sí.
     */
    public function estaPorCobrar(): bool
    {
        return ! $this->estaCobrado()
            && $this->estado !== self::ESTADO_CANCELADO;
    }
}