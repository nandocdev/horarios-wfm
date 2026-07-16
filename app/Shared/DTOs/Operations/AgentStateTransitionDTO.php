<?php

declare(strict_types=1);

namespace App\Shared\DTOs\Operations;

use Spatie\LaravelData\Data;

final class AgentStateTransitionDTO extends Data
{
    public function __construct(
        public int $employee_id,
        public string $transition_time,
        public string $agent_state,
        public ?string $reason_code,
        public int $duration,
    ) {}
}
