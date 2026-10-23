<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    public const PAGADA = 'pagada';
    public const ANULADA = 'anulada';

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
        'caja_id',
        'estado',
        'total',
        'metodo_pago',
        'monto_recibido',
        'cambio',
        'notas',
        'atendido_por',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'monto_recibido' => 'decimal:2',
            'cambio' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }

    public function estaPagada(): bool
    {
        return $this->estado === self::PAGADA;
    }
    public function entregaInsuficiente(): bool
    {
        return $this->monto_recibido !== null
            && (float) $this->monto_recibido < (float) $this->total;
    }
}