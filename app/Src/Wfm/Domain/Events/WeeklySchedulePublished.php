<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Events;

use App\Src\Shared\Domain\Events\DomainEvent;
use App\Src\Wfm\Domain\Entities\WeeklySchedule;

final class WeeklySchedulePublished extends DomainEvent
{
    public function __construct(
        public readonly WeeklySchedule $weeklySchedule,
        public readonly int $publishedByUserId,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'wfm.weekly_schedule.published';
    }
}
