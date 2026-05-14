<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\DTOs;

/**
 * DTO para la asignación de actividades intradía.
 */
class IntradayActivityDTO
{
    public function __construct(
        public readonly int $activity_definition_id,
        public readonly array $employee_ids,
        public readonly string $date,
        public readonly string $start_time,
        public readonly string $end_time,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            activity_definition_id: (int) $data['activity_definition_id'],
            employee_ids: (array) $data['employee_ids'],
            date: (string) $data['date'],
            start_time: (string) $data['start_time'],
            end_time: (string) $data['end_time'],
            notes: $data['notes'] ?? null,
        );
    }
}
