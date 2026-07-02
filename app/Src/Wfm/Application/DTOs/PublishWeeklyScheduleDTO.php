<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\DTOs;

final readonly class PublishWeeklyScheduleDTO
{
    public function __construct(
        public int $weeklyScheduleId,
        public int $publishedByUserId,
    ) {}
}
