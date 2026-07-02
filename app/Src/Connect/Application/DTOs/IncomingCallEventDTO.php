<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class IncomingCallEventDTO
{
    public function __construct(
        public string $provider,
        public array $payload,
    ) {}
}
