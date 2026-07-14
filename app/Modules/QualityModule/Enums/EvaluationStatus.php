<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Enums;

enum EvaluationStatus: string
{
    case PendienteCalibracion = 'pendiente_calibracion';
    case Activa = 'activa';
    case ConFeedback = 'con_feedback';
    case Eliminada = 'eliminada';

    public function label(): string
    {
        return match ($this) {
            self::PendienteCalibracion => 'Pendiente Calibración',
            self::Activa => 'Activa',
            self::ConFeedback => 'Con Feedback',
            self::Eliminada => 'Eliminada',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Activa, self::PendienteCalibracion, self::ConFeedback]);
    }
}
