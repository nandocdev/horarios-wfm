<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Listeners;

use App\Modules\AuditModule\Application\RecordAuditEntry\Command;
use App\Modules\AuditModule\Application\RecordAuditEntry\Handler;
use App\Shared\Events\WeeklySchedulePublished;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogWeeklySchedulePublishedListener implements ShouldQueue
{
    public function __construct(
        private Handler $handler,
    ) {}

    public function handle(WeeklySchedulePublished $event): void
    {
        $weekly = $event->weeklySchedule;

        $command = new Command(
            entityType: $weekly !== null ? get_class($weekly) : 'WeeklySchedule',
            entityId: $weekly?->id ?? 'unknown',
            action: 'weekly_schedule.published',
            before: null,
            after: $weekly?->toArray(),
            userId: $event->publishedByUserId,
        );

        ($this->handler)($command);
    }
}
