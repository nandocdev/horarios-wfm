<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Services;

use App\Src\Wfm\Domain\Entities\ScheduleAssignment;
use DateTimeImmutable;

final class GetExpectedAgentStateService
{
    public const STATE_OFF = 'OFF';
    public const STATE_SHIFT = 'SHIFT';
    public const STATE_INTRADAY = 'INTRADAY';
    public const STATE_EXCEPTION = 'EXCEPTION';

    public function execute(
        DateTimeImmutable $now,
        ?ScheduleAssignment $assignment = null,
        array $intradayActivities = [],
        array $exceptions = [],
    ): array {
        // 1. Excepciones
        foreach ($exceptions as $exc) {
            if (! isset($exc['start_at'], $exc['end_at'])) continue;
            $start = $exc['start_at'] instanceof DateTimeImmutable ? $exc['start_at'] : new DateTimeImmutable($exc['start_at']);
            $end = $exc['end_at'] instanceof DateTimeImmutable ? $exc['end_at'] : new DateTimeImmutable($exc['end_at']);

            if ($now >= $start && $now <= $end) {
                return [
                    'type' => self::STATE_EXCEPTION,
                    'label' => $exc['label'] ?? 'Excepción',
                    'is_productive' => false,
                    'color' => $exc['color'] ?? '#ef4444',
                ];
            }
        }

        // 2. Actividades intradía
        foreach ($intradayActivities as $activity) {
            if ($now >= $activity->startTime() && $now <= $activity->endTime()) {
                return [
                    'type' => self::STATE_INTRADAY,
                    'label' => $activity->activityTypeId() > 0 ? 'Actividad' : 'Actividad',
                    'is_productive' => false,
                    'color' => '#f59e0b',
                ];
            }
        }

        // 3. Jornada base (disponible)
        if ($assignment !== null && $assignment->startTime() && $assignment->endTime()) {
            $start = $assignment->startTime();
            $end = $assignment->endTime();

            if ($now >= $start && $now <= $end) {
                return [
                    'type' => self::STATE_SHIFT,
                    'label' => 'Disponible',
                    'is_productive' => true,
                    'color' => '#10b981',
                ];
            }
        }

        return [
            'type' => self::STATE_OFF,
            'label' => 'Fuera de Jornada',
            'is_productive' => false,
            'color' => '#6b7280',
        ];
    }
}
