<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Shared\Events\WeeklySchedulePublished;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditWeeklySchedulePublishedListener implements ShouldQueue
{
    public function handle(WeeklySchedulePublished $event): void
    {
        $weekly = $event->weeklySchedule;

        AuditLog::create([
            'entity_type' => get_class($weekly),
            'entity_id' => $weekly->id ?? null,
            'action' => 'weekly_schedule.published',
            'before' => null,
            'after' => $weekly?->toArray(),
            'ip_address' => null,
            'user_id' => $event->publishedByUserId,
        ]);
    }
}
