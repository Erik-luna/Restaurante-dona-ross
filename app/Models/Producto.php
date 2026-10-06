<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Producto extends Model
{
    /**
     * La disponibilidad de un producto, como subconsulta de SQL.
     *
     * Es la misma fórmula que StockJornada::disponible(), escrita en SQL porque se
     * necesita para filtrar y ordenar el catálogo sin cargar una fila por producto.
     *
     * La fecha se filtra con un RANGO (`>= inicio` y `< siguiente día`) y no con
     * `fecha = ?` por una razón concreta: la columna es `date` en MySQL, pero en
     * SQLite el mismo tipo guarda 'Y-m-d 00:00:00'. Comparar contra '2026-10-04'
     * funcionaría en producción y devolvería cero en los tests, y el fallo aparecería
     * justo donde más estorba: en el catálogo vacío. El rango funciona en los dos
     * motores y además sigue usando el índice de `fecha`, cosa que un
     * `DATE(fecha) = ?` no haría.
     *
     * Está en una constante y no repetida en tres métodos a propósito: si algún día
     * la fórmula cambia (por ejemplo, si se añade una columna), sólo se cambia aquí
     * y en el modelo, y las dos cosas no pueden separarse por accidente.
     */
    public const EXPRESION_DISPONIBLE = '(SELECT COALESCE(j.inicial, 0) + COALESCE(j.entradas, 0)'
        .' - COALESCE(j.mermas, 0) - COALESCE(j.consumidos, 0)'
        .' FROM stock_jornadas j WHERE j.producto_id = productos.id'
        .' AND j.fecha >= ? AND j.fecha < ?)';

    /**
     * Los dos valores que EXPRESION_DISPONIBLE espera como enlaces.
     *
     * Devuelve el array en el orden en que aparecen los `?` de la constante, y en el
     * formato `Y-m-d H:i:s`, que es el que entienden los dos motores.
     *
     * @return array<int, string>
     */
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
        return str_starts_with($this->imagen, 'http')
            ? $this->imagen
            : asset('storage/' . $this->imagen);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Líneas de pedido que referencian este producto.
     *
     * Existe para poder responder "¿este producto se puede borrar?" sin contar
     * sobre una tabla desnormalizada, y sobre todo para que el borrado físico
     * quede condicionado al historial: un producto pedido alguna vez no se
     * elimina, se desactiva.
     */
    public function pedidoItems(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /**
 * Líneas de venta de salón que referencian este producto.
 *
 * Van aparte de pedidoItems() porque las ventas tienen su propia tabla. Un
 * producto puede tener historial en las dos: se vendió en el salón y además
 * alguien lo pidió a domicilio.
 */
public function ventaItems(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }

    /** Todas las jornadas de stock de este producto, una por día servido. */
    public function jornadas(): HasMany
    {
        return $this->hasMany(StockJornada::class);
    }

    /**
     * La jornada de hoy. Puede ser null: un producto recién creado no tiene
     * porciones cargadas, y eso significa que no tiene nada disponible.
     */
    public function jornadaDeHoy(?string $fecha = null): ?StockJornada
    {
        return StockJornada::de($this->id, $fecha);
    }

    /**
     * Cuántas porciones quedan disponibles en una fecha.
     *
     * La fórmula es la de StockJornada::disponible() y está escrita aquí también
     * como SQL porque el catálogo filtra y ordena por este número. Es la única
     * duplicación del cálculo en todo el sistema, y está comentada en
     * StockJornada para que si algún día cambia la fórmula se cambien los dos
     * sitios: hay un test que compara ambos.
     */
    public function disponibleEn(?string $fecha = null): int
    {
        // Dos trampas de Eloquent que aparecen aquí y conviene no repetir:
        //
        // 1. `value('disponible')` llama a first(['disponible']) y eso reemplaza el
        //    select de la consulta, dejando de calcular la subconsulta: el
        //    resultado sería NULL siempre, es decir, cero para todo.
        // 2. `addSelect(['disponible' => EXPRESION_DISPONIBLE])` es peor todavía:
        //    como el valor es una cadena y no una subconsulta, Laravel lo mete en
        //    la lista de columnas y el motor lo entrecomilla como si fuera un
        //    nombre de campo, produciendo un error de sintaxis. Para una
        //    expresión hace falta selectRaw() o selectSub(), nunca addSelect() con
        //    texto plano.
        $fila = $this->newQuery()
            ->selectRaw(static::EXPRESION_DISPONIBLE.' as disponible', static::rangoDeFecha($fecha))
            ->whereKey($this->getKey())
            ->first();

        return (int) ($fila?->disponible ?? 0);
    }

    /**
     * Sólo los productos con porciones disponibles en la fecha dada.
     *
     * El filtro y la columna salen de la misma subconsulta, y por eso el `disponible`
     * que se lee en la vista y el que decide si el producto aparece en el catálogo
     * no pueden discrepar.
     */
    public function scopeConDisponible(Builder $query, ?string $fecha = null, int $minimo = 1): Builder
    {
        $rango = static::rangoDeFecha($fecha);
        $sql = static::EXPRESION_DISPONIBLE;

        return $query
            ->select('productos.*')
            ->selectRaw($sql.' as disponible', $rango)
            ->whereRaw($sql.' >= ?', [...$rango, $minimo]);
    }

    /** ¿Este producto aparece en algún historial de pedido o de venta? */
    public function tieneHistorial(): bool
    {
        return $this->pedidoItems()->exists() || $this->ventaItems()->exists();
    }
}
