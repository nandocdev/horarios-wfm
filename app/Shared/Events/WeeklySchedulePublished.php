<?php

declare(strict_types=1);

namespace App\Shared\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeeklySchedulePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $weeklySchedule,
        public int|string $publishedByUserId
    ) {}
}
