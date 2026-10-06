<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una línea de una venta: un producto y cuántas unidades se vendieron.
 *
 * Guarda el precio con el que se vendió, no el precio actual del producto. Si
 * mañana suben los precios, el total de una venta de hace un mes tiene que seguir
 * diciendo lo que se cobró ese día, no lo que valdría hoy.
 */
class VentaItem extends Model
{
    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}