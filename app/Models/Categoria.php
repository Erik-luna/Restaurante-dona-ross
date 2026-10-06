<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    /** La categoría se muestra en el catálogo. */
    public const ESTADO_ACTIVA = 'activo';

    /** La categoría se ocultó del catálogo, pero sus platos no se borran. */
    public const ESTADO_INACTIVA = 'inactivo';

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    /**
     * Traduce el estado de la categoría a un sí o un no.
     *
     * En el resto del sistema el estado se decide con `activo => true`; aquí la
     * columna guarda texto ('activo' / 'inactivo'). Este método evita repetir la
     * comparación en cada consulta y en cada vista.
     */
    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    /** Sólo las categorías visibles en el catálogo. */
    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::ESTADO_ACTIVA);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}