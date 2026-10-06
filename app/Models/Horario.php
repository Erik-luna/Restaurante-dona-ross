<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Horario de atención de un día de la semana.
 *
 * El modelo es dueño de la pregunta "¿está abierto ahora?" porque esa respuesta
 * depende de tres cosas a la vez --el día, la hora y si el día está activo-- y
 * repetir esa combinación en la portada, en el catálogo y en el panel dejaría
 * tres sitios donde un cambio futuro solo se aplicaría a uno de ellos.
 */
class Horario extends Model
{
    /** Días en orden ISO: 1 = lunes ... 7 = domingo. */
    public const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    protected $fillable = [
        'dia',
        'apertura',
        'cierre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'dia' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function nombreDia(): string
    {
        return self::DIAS[$this->dia] ?? '—';
    }

    /** El horario tal como se muestra: "08:00 - 20:00". */
    public function rango(): string
    {
        return $this->apertura.' - '.$this->cierre;
    }

    /**
     * ¿Está abierto en este momento?
     *
     * La comparación es de cadenas "HH:MM:SS", que es como MySQL y SQLite
     * devuelven una columna TIME. Ordenan igual que los números porque todos los
     * valores tienen dos puntos en el mismo sitio: "09:30:00" < "10:00:00" es
     * cierto como texto y como hora.
     *
     * Se acepta un cierre anterior a la apertura a propósito: un restaurante que
     * cierra a las 02:00 de la madrugada está abierto de noche y con la condición
     * simple saldría siempre cerrado. Cuando el cierre es menor que la apertura se
     * entiende que el turno pasa la medianoche.
     */
    public function estaAbierto(?string $hora = null): bool
    {
        if (! $this->activo) {
            return false;
        }

        $hora ??= now()->format('H:i:s');
        $cruzaMedianoche = $this->cierre <= $this->apertura;

        return $cruzaMedianoche
            ? ($hora >= $this->apertura || $hora < $this->cierre)
            : ($hora >= $this->apertura && $hora < $this->cierre);
    }

    /** El horario del día de hoy, si está configurado. */
    public static function deHoy(): ?self
    {
        return static::where('dia', now()->isoWeekday())->first();
    }

    /**
     * Los horarios agrupados por rango, como "Lunes a Viernes: 08:00 - 20:00".
     *
     * Se agrupan días contiguos porque en un pie de página siete filas donde cinco
     * dicen lo mismo se leen peor que tres. Un día que queda en medio de otro grupo
     * con las mismas horas se cuela en él; si se intercalara con uno distinto, se
     * corta el grupo y empieza otro.
     *
     * @return array<int, array{etiqueta: string, rango: string, activo: bool}>
     */
    public static function agrupados(): array
    {
        $grupos = [];

        foreach (static::orderBy('dia')->get() as $horario) {
            $ultimo = $grupos === [] ? null : $grupos[array_key_last($grupos)];

            if ($ultimo !== null
                && $ultimo['rango'] === $horario->rango()
                && $ultimo['activo'] === $horario->activo
                && $ultimo['ultimoDia'] + 1 === $horario->dia) {
                $grupos[array_key_last($grupos)]['ultimoDia'] = $horario->dia;
                $grupos[array_key_last($grupos)]['etiqueta'] = self::etiquetaDeRango(
                    $grupos[array_key_last($grupos)]['primerDia'],
                    $horario->dia
                );

                continue;
            }

            $grupos[] = [
                'primerDia' => $horario->dia,
                'ultimoDia' => $horario->dia,
                'etiqueta' => $horario->nombreDia(),
                'rango' => $horario->rango(),
                'activo' => $horario->activo,
            ];
        }

        return array_map(static fn (array $grupo) => [
            'etiqueta' => $grupo['etiqueta'],
            'rango' => $grupo['rango'],
            'activo' => $grupo['activo'],
        ], $grupos);
    }

    /**
     * Etiqueta de un grupo de días: "Sábado", "Lunes y Martes", "Lunes a Viernes".
     *
     * Para tres días o más se usa "de X a Y" y no la lista con comas porque en un
     * pie de página estrecho "Lunes, Martes, Miércoles, Jueves y Viernes" ocupa
     * tres líneas. Es la convención de los horarios impresos y de las agendas, así
     * que se lee sin esfuerzo. El caso de dos días sí lleva "y", porque en español
     * dos elementos enumrados no llevan coma antes de la conjunción.
     */
    private static function etiquetaDeRango(int $desde, int $hasta): string
    {
        $primero = self::DIAS[$desde] ?? '—';
        $ultimo = self::DIAS[$hasta] ?? '—';

        if ($desde === $hasta) {
            return $primero;
        }

        return $hasta === $desde + 1
            ? $primero.' y '.$ultimo
            : $primero.' a '.$ultimo;
    }
}