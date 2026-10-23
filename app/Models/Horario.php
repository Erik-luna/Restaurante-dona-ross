<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
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

    public function rango(): string
    {
        return $this->apertura.' - '.$this->cierre;
    }

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
    public static function deHoy(): ?self
    {
        return static::where('dia', now()->isoWeekday())->first();
    }
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