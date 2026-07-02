<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class CallStartDTO
{
    public function __construct(
        public int $queueId,
        public int $employeeId,
        public string $phoneNumber,
        public ?string $citizenIdentifier = null,
        public ?string $ivrStartedAt = null,
    ) {}
}
