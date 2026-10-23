<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_PREPARANDO = 'preparando';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_ENTREGADO = 'entregado';
    public const ESTADO_CANCELADO = 'cancelado';
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function estaCobrado(): bool
    {
        return $this->pagado_at !== null;
    }

    public function estaPorCobrar(): bool
    {
        return ! $this->estaCobrado()
            && $this->estado !== self::ESTADO_CANCELADO;
    }
}