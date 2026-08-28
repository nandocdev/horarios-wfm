<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Enums;

use Illuminate\Support\Collection;

/**
 * Enum para género del empleado.
 * Valores: M (Masculino), F (Femenino), O (Otro)
 */
enum Gender: string
{
    case Masculino = 'M';
    case Femenino = 'F';
    case Otro = 'O';

    /**
     * Obtiene la etiqueta legible para el género.
     */
    public function label(): string
    {
        return match ($this) {
            self::Masculino => 'Masculino',
            self::Femenino => 'Femenino',
            self::Otro => 'Otro',
        };
    }

    /**
     * Obtiene todas las opciones para select.
     *
     * @return Collection<string, string>
     */
    public static function options(): Collection
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()]);
    }

    /**
     * Verifica si un valor es válido para este enum.
     */
    public static function isValid(string $value): bool
    {
        return collect(self::cases())->contains(fn (self $case) => $case->value === $value);
    }
}
