<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Shared\Events\WeeklySchedulePublished;
use App\Src\Platform\Infrastructure\Persistence\AuditLogBridge;
use Illuminate\Contracts\Queue\ShouldQueue;

final class AuditWeeklySchedulePublishedListener implements ShouldQueue {
    public function handle(WeeklySchedulePublished $event): void {
        $weekly = $event->weeklySchedule;

        AuditLogBridge::logCustom(
            entityType: get_class($weekly),
            entityId: $weekly?->id,
            action: 'weekly_schedule.published',
            before: null,
            after: $weekly?->toArray(),
            ipAddress: null,
            userId: $event->publishedByUserId,
        );
    }
}
