<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Src\Platform\Infrastructure\Persistence\AuditLogBridge;
use App\Src\Wfm\Domain\Events\WeeklySchedulePublished;
use Illuminate\Contracts\Queue\ShouldQueue;

final class AuditSrcWeeklySchedulePublishedListener implements ShouldQueue {
    public function handle(WeeklySchedulePublished $event): void {
        AuditLogBridge::logCustom(
            entityType: get_class($event),
            entityId: $event->weeklyScheduleId,
            action: 'weekly_schedule.published',
            before: null,
            after: [
                'weekly_schedule_id' => $event->weeklyScheduleId,
                'published_by' => $event->publishedByUserId,
            ],
            ipAddress: null,
            userId: $event->publishedByUserId,
        );
    }
}
