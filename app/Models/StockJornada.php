<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

/**
 * Las porciones de un producto durante un día.
 *
 * Este modelo es el dueño del único cálculo del stock:
 *
 *   disponible = inicial + entradas - mermas - consumidos
 *
 * Vive aquí y no en las consultas porque el número se usa en el catálogo, en el
 * carrito, al registrar una venta y al cerrar el arqueo. Duplicar la fórmula en
 * esos sitios garantizaría que uno de ellos se queda atrás, y el síntoma sería
 * vender algo que ya no hay.
 */
class StockJornada extends Model
{
    /**
     * Las tres acciones que puede hacer una persona sobre el stock de un día.
     *
     * Son las del formulario de ajuste, y también las de StockService. Están aquí
     * como constantes para que el controlador, el request y el servicio hablen del
     * mismo vocabulario y no de cadenas sueltas escritas a mano en tres archivos.
     */
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

    /** Quién hizo el último ajuste manual. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Cuántas porciones se pueden vender todavía en esta jornada. */
    public function disponible(): int
    {
        return $this->inicial + $this->entradas - $this->mermas - $this->consumidos;
    }

    public function estaAgotada(): bool
    {
        return $this->disponible() <= 0;
    }

    /**
     * La jornada de un producto para una fecha dada.
     *
     * Puede no existir: un producto recién creado no tiene fila hasta que se le
     * cargan porciones, y eso es correcto, porque un plato sin carga no tiene
     * nada disponible.
     */
    public static function de(int $productoId, ?string $fecha = null): ?self
    {
        return static::where('producto_id', $productoId)
            ->whereDate('fecha', $fecha ?? now()->toDateString())
            ->first();
    }

/**
     * Fija la carga inicial del día y devuelve la jornada.
     *
     * Esta función PISA `inicial`, y es a propósito: corregir un número mal
     * cargado por la mañana tiene que ser posible, y la forma natural de hacerlo
     * es volver a cargar el día con la cifra correcta. Por eso quien sólo quiere
     * leer la jornada del día sin crearla debe usar `de()`, y quien quiere
     * obtenerla creando una vacía debe usar `obtenerOCrear()`.
     */
    public static function abrir(int $productoId, int $inicial = 0, ?int $userId = null): self
    {
        return static::obtenerOCrear($productoId)->fijarInicial($inicial, $userId);
    }

    /**
     * Fija la carga inicial del día sobre una jornada que ya existe.
     *
     * PISA `inicial`, y es a propósito: corregir una cifra mal cargada por la mañana
     * tiene que ser posible, y la forma natural de hacerlo es volver a cargar el día
     * con el número correcto. Quien sólo quiera añadir, use `reponer()`.
     */
    public function fijarInicial(int $cantidad, ?int $userId = null): self
    {
        $this->forceFill([
            'inicial' => max(0, $cantidad),
            'motivo' => 'Carga inicial de la jornada.',
            'user_id' => $userId,
        ])->save();

        return $this;
    }

/**
 * La jornada de un producto para una fecha, creando una vacía si no existe.
 *
 * No toca `inicial` si la fila ya existe. Esa diferencia es la razón de que
* exista esta función aparte de `abrir()`: el carrito necesita una jornada
     * sobre la que trabajar aunque nadie haya cargado el producto, y si para
     * conseguirla llamara a `abrir()` con cero le borraría la carga que la cocina
     * acaba de hacer.
 *
 * La búsqueda usa `whereDate()` y no una comparación directa con `=`, porque el
 * mismo `firstOrCreate(['fecha' => '2026-10-04'])` que funciona en MySQL falla en
 * SQLite: allí la columna guarda '2026-10-04 00:00:00', la comparación no
 * encuentra la fila que ya existe y el intento de insertar se estrella contra el
 * índice único. Es un fallo que sólo aparece en los tests, justo donde se
 * ejecutan todos los días.
 *
 * La carrera entre dos personas que abren esto a la vez la resuelve el índice
 * único (producto_id, fecha): si las dos inserciones fallan, la segunda recupera
 * la fila en lugar de duplicarla.
 */
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
            // Otra transacción insertó la fila entre la búsqueda y el create.
            return static::de($productoId, $fecha) ?? throw $e;
        }
    }

    /**
     * Reposición de porciones durante el servicio.
     *
     * Suma a `entradas` en lugar de pisar un número absoluto: así el histórico
     * deja ver que el plato se repuso dos veces y por qué, en vez de mostrar
     * solamente el total final.
     */
    public function reponer(int $cantidad, ?string $motivo = null, ?int $userId = null): self
    {
        $this->forceFill([
            'entradas' => $this->entradas + max(0, $cantidad),
            'motivo' => $motivo ?: 'Reposición de porciones durante el servicio.',
            'user_id' => $userId,
        ])->save();

        return $this;
    }

    /**
     * Merma: porciones que se perdieron sin venderse.
     *
     * Van separadas de `consumidos` porque no son una venta. Si una merma se
     * contara como consumo, el informe de platos más vendidos incluiría platos
     * que nadie compró, y la cocina no podría distinguir un error de preparación
     * de una pérdida.
     */
    public function registrarMerma(int $cantidad, string $motivo, ?int $userId = null): self
    {
        $this->forceFill([
            'mermas' => $this->mermas + max(0, $cantidad),
            'motivo' => $motivo,
            'user_id' => $userId,
        ])->save();

        return $this;
    }

    /**
     * Consumo por venta de salón o por pedido de delivery.
     *
     * Sólo suma; quien llama es responsable de comprobar antes que hay
     * suficiente, porque la comprobación y el descuento tienen que ocurrir juntos
     * dentro de la misma transacción para que dos ventas simultáneas no puedan
     * gastarse las mismas porciones.
     */
    public function consumir(int $cantidad): self
    {
        $this->forceFill(['consumidos' => $this->consumidos + max(0, $cantidad)])->save();

        return $this;
    }
}