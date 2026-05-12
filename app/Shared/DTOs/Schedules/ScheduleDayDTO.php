<?php

declare(strict_types=1);

namespace App\Shared\DTOs\Schedules;

/**
 * DTO para representar el horario programado de un empleado en un día.
 */
final readonly class ScheduleDayDTO {
    public function __construct(
        public int $employee_id,
        public string $date,
        public ?string $start_time,
        public ?string $end_time,
        public ?string $lunch_start_time = null,
        public ?string $lunch_end_time = null,
        public ?string $break_start_time = null,
        public ?string $break_end_time = null,
        public int $lunch_minutes = 45,
        public int $break_minutes = 15,
        public bool $is_off = false,
        public array $exceptions = []
    ) {
    }
}