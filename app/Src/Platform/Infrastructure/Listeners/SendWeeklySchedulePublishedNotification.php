<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Shared\Events\WeeklySchedulePublished;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendWeeklySchedulePublishedNotification implements ShouldQueue {
    private const array TARGET_ROLES = [
        'operator',
        'coordinator',
        'supervisor',
        'chief',
        'director',
        'admin',
        'wfm',
    ];

    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(WeeklySchedulePublished $event): void {
        $schedule = $event->weeklySchedule;

        $users = User::where('is_active', true)
            ->whereHas('roles', fn($q) => $q->whereIn('name', self::TARGET_ROLES))
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $scheduleName = $schedule?->name
            ?? $schedule?->week_label
            ?? 'Horario semanal';

        foreach ($users as $user) {
            $notification = InAppNotification::create(
                id: (string) Str::uuid(),
                userId: (int) $user->id,
                type: 'schedule_published',
                notifiableType: get_class($schedule),
                notifiableId: (int) ($schedule?->id ?? 0),
                title: 'Horario semanal publicado',
                message: "El {$scheduleName} ha sido publicado.",
                data: [
                    'schedule_id' => $schedule?->id,
                    'published_by' => $event->publishedByUserId,
                ],
            );

            $this->notificationRepository->save($notification);

            $this->broadcastChannel->send(
                notifiable: $user,
                title: 'Horario semanal publicado',
                message: "El {$scheduleName} ha sido publicado.",
                level: 'info',
                icon: 'calendar-check',
                actionUrl: '/wfm/schedule',
            );
        }
    }
}
