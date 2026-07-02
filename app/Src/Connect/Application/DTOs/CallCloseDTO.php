<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class CallCloseDTO
{
    public function __construct(
        public int $callRecordId,
    ) {}
}
