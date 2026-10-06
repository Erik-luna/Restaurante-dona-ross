<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de efectivo de una caja.
 *
 * No es una venta: son entradas y salidas que no vienen de un pedido (un retiro a
 * la bóveda, un cambio, un gasto del día). Si no existieran, el arqueo compararía
 * el dinero contado contra ventas y siempre aparecería un descuadre cuando el
 * restaurante moviera caja por otra razón.
 */
class CajaMovimiento extends Model
{
    public const ENTRADA = 'entrada';
    public const SALIDA = 'salida';

    protected $fillable = [
        'caja_id',
        'tipo',
        'monto',
        'concepto',
        'user_id',
    ];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2'];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    /** Quién registró el movimiento. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function esEntrada(): bool
    {
        return $this->tipo === self::ENTRADA;
    }
}