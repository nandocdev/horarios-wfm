<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Enums;

enum EvaluationStatus: string
{
    case Activa = 'activa';
    case Eliminada = 'eliminada';

    public function label(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Eliminada => 'Eliminada',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Activa;
    }
}
