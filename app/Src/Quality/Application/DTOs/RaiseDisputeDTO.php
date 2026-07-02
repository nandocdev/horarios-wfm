<?php

declare(strict_types=1);

namespace App\Src\Quality\Application\DTOs;

final readonly class RaiseDisputeDTO
{
    public function __construct(
        public int $evaluationId,
        public int $agentId,
        public string $reason,
    ) {}
}
