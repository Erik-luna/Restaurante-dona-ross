<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Caja del día: una por persona y por fecha.
 *
 * El modelo es dueño del cálculo del arqueo (método resumen()) para que ese
 * número no se escriba a mano en tres sitios distintos: lo usan por igual el
 * cierre, la vista de detalle y el índice. Si el cálculo viviera duplicado en
 * el controlador y en la vista, tarde o temprano los dos mostrarían cifras
 * distintas y el arqueo dejaría de ser confiable.
 */
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

    /** La persona que abrió la caja. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Las ventas de salón que entraron por esta caja. */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Los cobros de los pedidos de delivery que esta caja cobró.
     *
     * Van aparte de las ventas porque son dinero de otra operación: una venta se
     * paga en el mostrador y un pedido se cobra después de entregado.
     */
    public function cobros(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /** Entradas y salidas de efectivo ajenas a las ventas. */
    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === self::ABIERTA;
    }

    /**
     * La caja abierta de esta persona hoy, si la tiene.
     *
     * Se usa antes de cada venta y de cada cobro. Devuelve null en vez de
     * lanzar error para que quien llama decida qué hacer: el controlador
     * muestra un mensaje, un test puede comprobarlo.
     */
    public static function abiertaDeHoy(int $userId, ?string $fecha = null): ?self
    {
        return static::where('user_id', $userId)
            ->whereDate('fecha', $fecha ?? today())
            ->where('estado', self::ABIERTA)
            ->first();
    }

    /**
     * Los números del arqueo calculados con los datos de ahora.
     *
     * La fórmula es la del conteo de una caja real:
     *
     *   esperado = monto inicial + ventas + cobros + entradas - salidas
     *
     * El dinero entra de tres sitios y se cuenta por separado, porque mezclarlos
     * dejaría sin explicación cualquier descuadre: si sobró dinero, no se sabría
     * si fue por ventas, por cobros de delivery o por una entrada mal anotada.
     *
     * Se excluyen las ventas anuladas y los pedidos cancelados: se preparó la
     * comida pero el cliente no pagó, así que nunca debió entrar dinero por
     * ellos. Y se filtra por pagado_at para que un pedido cocinado pero todavía no
     * cobrado no cuente antes de tiempo.
     */
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
            // Mientras la caja está abierta nadie ha contado el dinero todavía.
            'declarado' => (float) ($this->total_declarado ?? 0),
            'diferencia' => (float) ($this->total_declarado ?? 0) - $esperado,
        ];
    }

    /**
     * El arqueo tal como quedó guardado al cerrar.
     *
     * Una caja cerrada usa los valores congelados en la fila, no resumen(): si
     * alguien pagara un pedido antiguo más tarde, resumen() ya no debe poder
     * cambiar el cierre de un turno que terminó.
     */
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

    /**
     * ¿El arqueo cuadró?
     *
     * Se compara con dos decimales porque es dinero: 0.001 de diferencia es
     * ruido de coma flotante, no un descuadre.
     */
    public function cuadra(): bool
    {
        return round((float) $this->diferencia, 2) === 0.0;
    }
}