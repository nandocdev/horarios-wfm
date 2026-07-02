<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class CallCompleteDTO
{
    public function __construct(
        public int $callRecordId,
        public int $talkTime,
        public int $handleTime,
        public int $contactDisposition,
    ) {}
}
