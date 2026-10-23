<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function esEntrada(): bool
    {
        return $this->tipo === self::ENTRADA;
    }
}