<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\DTOs;

/**
 * DTO para transportar datos de desempeño estandarizados.
 */
final readonly class StandardizedPerformanceDTO
{
    public function __construct(
        public string $date,
        public array $attendance,
        public array $activities,
        public array $reasons,
        public array $metrics,
        public array $queues,
        public array $goals = []
    ) {}

    public static function empty(string $date): self
    {
        return new self(
            date: $date,
            attendance: [
                'scheduled_entry' => null,
                'actual_entry' => null,
                'diff_minutes' => 0,
                'status' => 'absent',
                'lunch' => [
                    'scheduled_start' => null,
                    'actual_start' => null,
                    'diff_minutes' => 0,
                    'actual_duration' => 0,
                    'scheduled_duration' => 45,
                ],
                'break' => [
                    'scheduled_start' => null,
                    'actual_start' => null,
                    'diff_minutes' => 0,
                    'actual_duration' => 0,
                    'scheduled_duration' => 15,
                ],
            ],
            activities: [],
            reasons: [],
            metrics: [
                'total_scheduled_minutes' => 0,
                'total_productive_minutes' => 0,
                'total_connected_minutes' => 0,
                'productivity_percentage' => 0,
                'utilization_percentage' => 0,
                'adherence_percentage' => 0,
            ],
            queues: [],
            goals: []
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'attendance' => $this->attendance,
            'activities' => $this->activities,
            'reasons' => $this->reasons,
            'metrics' => $this->metrics,
            'queues' => $this->queues,
            'goals' => $this->goals,
        ];
    }
}
