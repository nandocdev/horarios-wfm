<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\DTOs;

final class EmployeePerformanceDTO
{
    public function __construct(
        public readonly string $date,
        public readonly array $attendance,
        public readonly array $activities,
        public readonly array $reasons,
        public readonly array $metrics,
        public readonly array $queues
    ) {}

    public static function empty(string $date): self
    {
        return new self(
            date: $date,
            attendance: [
                'scheduled_entry' => null,
                'actual_entry' => null,
                'diff_minutes' => 0,
                'status' => 'no_schedule',
                'lunch' => ['actual_start' => null, 'actual_duration' => 0, 'scheduled_duration' => 0],
                'break' => ['actual_start' => null, 'actual_duration' => 0, 'scheduled_duration' => 0],
            ],
            activities: [],
            reasons: [],
            metrics: [
                'total_scheduled_minutes' => 0,
                'total_productive_minutes' => 0,
                'total_connected_minutes' => 0,
                'total_logout_minutes' => 0,
                'productivity_percentage' => 0,
                'utilization_percentage' => 0,
                'occupancy' => 0,
            ],
            queues: []
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
        ];
    }
}
