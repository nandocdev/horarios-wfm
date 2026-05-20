<?php

declare(strict_types=1);

namespace App\Shared\DTOs\Telemetry;

/**
 * DTO para representar el estado de telemetría de un agente.
 */
final readonly class TelemetryStateDTO
{
    public function __construct(
        public int $employee_id,
        public string $current_state,
        public ?string $reason_code,
        public string $last_changed_at,
        public array $metadata = []
    ) {}
}
