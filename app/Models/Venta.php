<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta de salón: lo que alguien consume en el local.
 *
 * Es una entidad aparte de Pedido, y no una variante de él. La diferencia no es
 * sólo de nombre: un pedido de delivery lo prepara la cocina y se cobra después,
 * mientras que una venta de salón se cobra en el mostrador en el momento en que se
 * registra. Por eso Pedido y Venta son dos tablas distintas y no una con una
 * columna de tipo.
 */
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

    /** Cliente de la mesa. Nulo si fue consumo sin dejar datos. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Caja donde entró el dinero de esta venta. */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    /** Persona que atendió la mesa. */
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

    /** ¿El cliente dejó menos dinero que el importe de la venta? */
    public function entregaInsuficiente(): bool
    {
        return $this->monto_recibido !== null
            && (float) $this->monto_recibido < (float) $this->total;
    }
}