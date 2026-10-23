<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Caja extends Model
{
    public const ABIERTA = 'abierta';
    public const CERRADA = 'cerrada';

    public const METODOS_PAGO = ['efectivo', 'qr', 'tarjeta'];

    protected $fillable = [
        'user_id',
        'fecha',
        'monto_inicial',
        'estado',
        'apertura_at',
        'cierre_at',
        'total_ventas',
        'total_cobros',
        'total_entradas',
        'total_salidas',
        'total_esperado',
        'total_declarado',
        'diferencia',
        'observacion_apertura',
        'observacion_cierre',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto_inicial' => 'decimal:2',
            'apertura_at' => 'datetime',
            'cierre_at' => 'datetime',
            'total_ventas' => 'decimal:2',
            'total_cobros' => 'decimal:2',
            'total_entradas' => 'decimal:2',
            'total_salidas' => 'decimal:2',
            'total_esperado' => 'decimal:2',
            'total_declarado' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
    public function cobros(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }
    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === self::ABIERTA;
    }
    public static function abiertaDeHoy(int $userId, ?string $fecha = null): ?self
    {
        return static::where('user_id', $userId)
            ->whereDate('fecha', $fecha ?? today())
            ->where('estado', self::ABIERTA)
            ->first();
    }
    public function resumen(): array
    {
        $ventas = (float) $this->ventas()
            ->where('estado', Venta::PAGADA)
            ->sum('total');

        $cobros = (float) $this->cobros()
            ->whereNotNull('pagado_at')
            ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
            ->sum('total');

        $movimientos = $this->movimientos()
            ->selectRaw('tipo, COALESCE(SUM(monto), 0) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $entradas = (float) ($movimientos['entrada'] ?? 0);
        $salidas = (float) ($movimientos['salida'] ?? 0);
        $esperado = (float) $this->monto_inicial + $ventas + $cobros + $entradas - $salidas;

        return [
            'ventas' => $ventas,
            'cobros' => $cobros,
            'ingresos' => $ventas + $cobros,
            'entradas' => $entradas,
            'salidas' => $salidas,
            'esperado' => $esperado,
            'declarado' => (float) ($this->total_declarado ?? 0),
            'diferencia' => (float) ($this->total_declarado ?? 0) - $esperado,
        ];
    }
    public function arqueo(): array
    {
        if ($this->estaAbierta() || $this->total_esperado === null) {
            return $this->resumen();
        }

        return [
            'ventas' => (float) $this->total_ventas,
            'cobros' => (float) $this->total_cobros,
            'ingresos' => (float) $this->total_ventas + (float) $this->total_cobros,
            'entradas' => (float) $this->total_entradas,
            'salidas' => (float) $this->total_salidas,
            'esperado' => (float) $this->total_esperado,
            'declarado' => (float) $this->total_declarado,
            'diferencia' => (float) $this->diferencia,
        ];
    }
    public function cuadra(): bool
    {
        return round((float) $this->diferencia, 2) === 0.0;
    }
}