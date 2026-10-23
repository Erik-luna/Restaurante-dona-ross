<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Producto extends Model
{
    public const EXPRESION_DISPONIBLE = '(SELECT COALESCE(j.inicial, 0) + COALESCE(j.entradas, 0)'
        .' - COALESCE(j.mermas, 0) - COALESCE(j.consumidos, 0)'
        .' FROM stock_jornadas j WHERE j.producto_id = productos.id'
        .' AND j.fecha >= ? AND j.fecha < ?)';

    public static function rangoDeFecha(?string $fecha = null): array
    {
        $inicio = Carbon::parse($fecha ?? now()->toDateString())->startOfDay();

        return [
            $inicio->format('Y-m-d H:i:s'),
            $inicio->copy()->addDay()->format('Y-m-d H:i:s'),
        ];
    }

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio',
        'imagen',
        'destacado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'destacado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    protected $appends = ['imagen_url'];

    public function getImagenUrlAttribute(): ?string
    {
        if (! $this->imagen) {
            return null;
        }

        if (str_starts_with(strtolower($this->imagen), 'http://') || str_starts_with(strtolower($this->imagen), 'https://')) {
            return $this->imagen;
        }

        $ruta = str_replace('\\', '/', ltrim($this->imagen, '/'));

        if (str_starts_with($ruta, 'public/')) {
            $ruta = substr($ruta, strlen('public/'));
        }

        if (str_starts_with($ruta, 'storage/')) {
            return asset($ruta);
        }

        if (str_starts_with($ruta, 'img/')) {
            return asset($ruta);
        }

        if (str_starts_with($ruta, 'productos/')) {
            return asset('storage/'.$ruta);
        }

        if (str_starts_with($ruta, 'platos/')) {
            return asset('img/'.$ruta);
        }

        return asset('img/platos/'.$ruta);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
    public function pedidoItems(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }
public function ventaItems(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }
    public function jornadas(): HasMany
    {
        return $this->hasMany(StockJornada::class);
    }
    public function jornadaDeHoy(?string $fecha = null): ?StockJornada
    {
        return StockJornada::de($this->id, $fecha);
    }
    public function disponibleEn(?string $fecha = null): int
    {
        $fila = $this->newQuery()
            ->selectRaw(static::EXPRESION_DISPONIBLE.' as disponible', static::rangoDeFecha($fecha))
            ->whereKey($this->getKey())
            ->first();

        return (int) ($fila?->disponible ?? 0);
    }
    public function scopeConDisponible(Builder $query, ?string $fecha = null, int $minimo = 1): Builder
    {
        $rango = static::rangoDeFecha($fecha);
        $sql = static::EXPRESION_DISPONIBLE;

        return $query
            ->select('productos.*')
            ->selectRaw($sql.' as disponible', $rango)
            ->whereRaw($sql.' >= ?', [...$rango, $minimo]);
    }
    public function tieneHistorial(): bool
    {
        return $this->pedidoItems()->exists() || $this->ventaItems()->exists();
    }
}
