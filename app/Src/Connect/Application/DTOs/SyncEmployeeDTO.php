<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class SyncEmployeeDTO
{
    public function __construct(
        public string $loginId,
        public string $firstName,
        public string $lastName,
        public ?string $email = null,
        public ?string $teamId = null,
        public ?string $extension = null,
    ) {}
}
