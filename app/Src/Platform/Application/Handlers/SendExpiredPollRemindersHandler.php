<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Domain\Repositories\PollRepositoryInterface;
use App\Src\Platform\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class SendExpiredPollRemindersHandler
{
    public function __construct(
        private PollRepositoryInterface $pollRepository,
        private UserRepositoryInterface $userRepository,
        private InAppNotificationRepositoryInterface $notificationRepository,
    ) {}

    public function execute(): array
    {
        $now = now();

        $expiredPolls = $this->pollRepository->findExpiredWithoutReminder($now);

        if (empty($expiredPolls)) {
            return ['polls' => 0, 'notifications' => 0];
        }

        $activeUsers = $this->userRepository->findActive();

        if (empty($activeUsers)) {
            $this->pollRepository->markReminderSent($expiredPolls, $now);
            return ['polls' => count($expiredPolls), 'notifications' => 0];
        }

        $notifications = [];

        foreach ($expiredPolls as $poll) {
            $voterIds = $this->pollRepository->findVoterIds($poll->id());

            foreach ($activeUsers as $user) {
                if (in_array($user->id(), $voterIds, true)) {
                    continue;
                }

                $notifications[] = InAppNotification::create(
                    userId: $user->id(),
                    type: 'poll_expired',
                    title: 'Encuesta expirada',
                    message: "La encuesta \"{$poll->question()}\" ha expirado.",
                    data: [
                        'poll_id' => $poll->id(),
                        'expires_at' => $poll->expiresAt()?->format('Y-m-d H:i:s'),
                    ],
                    expiresAt: $now->copy()->addDays(7)->toDateTimeString(),
                );
            }
        }

        DB::transaction(function () use ($notifications, $expiredPolls, $now) {
            foreach ($notifications as $notification) {
                $this->notificationRepository->save($notification);
            }

            $this->pollRepository->markReminderSent($expiredPolls, $now);
        });

        return [
            'polls' => count($expiredPolls),
            'notifications' => count($notifications),
        ];
    }
}
