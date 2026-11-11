<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    protected $table = 'promociones';
    protected $fillable = [
        'titulo',
        'descripcion',
        'descuento_porcentaje',
        'fecha_inicio',
        'fecha_fin',
        'imagen',
        'activo',
    ];

    /**
     * Promociones activas y dentro de su período de vigencia
     * (fecha_inicio nula o <= hoy, fecha_fin nula o >= hoy, fechas inclusivas).
     */
    public function scopeVigentes(Builder $query): Builder
    {
        $hoy = now()->toDateString();

        return $query
            ->where('activo', true)
            ->where(function (Builder $consulta) use ($hoy) {
                $consulta->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', $hoy);
            })
            ->where(function (Builder $consulta) use ($hoy) {
                $consulta->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $hoy);
            });
    }

    protected function casts(): array
    {
        return [
            'descuento_porcentaje' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
        ];
    }

    protected $appends = ['imagen_url'];

    public function getImagenUrlAttribute(): ?string
    {
        if (! $this->imagen) {
            return null;
        }
        return str_starts_with($this->imagen, 'http')
            ? $this->imagen
            : asset('storage/' . $this->imagen);
    }
}
