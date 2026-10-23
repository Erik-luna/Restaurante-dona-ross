<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    public const ESTADO_ACTIVA = 'activo';
    public const ESTADO_INACTIVA = 'inactivo';

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::ESTADO_ACTIVA);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}