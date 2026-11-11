<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;


class StockJornada extends Model
{
    public const ACCION_CARGAR = 'cargar';
    public const ACCION_REPONER = 'reponer';
    public const ACCION_MERMA = 'merma';

    protected $fillable = [
        'producto_id',
        'fecha',
        'inicial',
        'entradas',
        'mermas',
        'consumidos',
        'motivo',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'inicial' => 'integer',
            'entradas' => 'integer',
            'mermas' => 'integer',
            'consumidos' => 'integer',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function disponible(): int
    {
        return $this->inicial + $this->entradas - $this->mermas - $this->consumidos;
    }

    public function estaAgotada(): bool
    {
        return $this->disponible() <= 0;
    }

    public static function de(int $productoId, ?string $fecha = null): ?self
    {
        return static::where('producto_id', $productoId)
            ->whereDate('fecha', $fecha ?? now()->toDateString())
            ->first();
    }

    public static function abrir(int $productoId, int $inicial = 0, ?int $userId = null): self
    {
        return static::obtenerOCrear($productoId)->fijarInicial($inicial, $userId);
    }

    public function fijarInicial(int $cantidad, ?int $userId = null): self
    {
        $this->forceFill([
            'inicial' => max(0, $cantidad),
            'motivo' => 'Carga inicial de la jornada.',
            'user_id' => $userId,
        ])->save();

        return $this;
    }

public static function obtenerOCrear(int $productoId, ?string $fecha = null): self
    {
        $jornada = static::de($productoId, $fecha);

        if ($jornada !== null) {
            return $jornada;
        }

        try {
            return static::create([
                'producto_id' => $productoId,
                'fecha' => $fecha ?? now()->toDateString(),
            ]);
        } catch (QueryException $e) {
            return static::de($productoId, $fecha) ?? throw $e;
        }
    }
    public function reponer(int $cantidad, ?string $motivo = null, ?int $userId = null): self
    {
        $this->forceFill([
            'entradas' => $this->entradas + max(0, $cantidad),
            'motivo' => $motivo ?: 'Reposición de porciones durante el servicio.',
            'user_id' => $userId,
        ])->save();

        return $this;
    }
    public function registrarMerma(int $cantidad, string $motivo, ?int $userId = null): self
    {
        $this->forceFill([
            'mermas' => $this->mermas + max(0, $cantidad),
            'motivo' => $motivo,
            'user_id' => $userId,
        ])->save();

        return $this;
    }
    public function consumir(int $cantidad): self
    {
        $this->forceFill(['consumidos' => $this->consumidos + max(0, $cantidad)])->save();

        return $this;
    }

    public function devolver(int $cantidad): self
    {
        $this->forceFill(['consumidos' => max(0, $this->consumidos - max(0, $cantidad))])->save();

        return $this;
    }
}