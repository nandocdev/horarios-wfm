<?php

declare(strict_types=1);

namespace App\Src\Workflows\Application\DTOs;

final readonly class SubmitApprovalRequestDTO
{
    public function __construct(
        public string $type,
        public int $requesterId,
        public array $payload = [],
        public ?string $reason = null,
        public int $requiredLevels = 1,
    ) {}
}
