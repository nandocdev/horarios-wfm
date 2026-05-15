<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Envía recordatorios automáticos cuando una encuesta expira.
 */
class SendExpiredPollRemindersAction
{
    /**
     * @return array{polls:int,notifications:int}
     */
    public function execute(): array
    {
        $now = now();

        $expiredPolls = Poll::query()
            ->where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->whereNull('reminder_sent_at')
            ->get();

        if ($expiredPolls->isEmpty()) {
            return ['polls' => 0, 'notifications' => 0];
        }

        $activeUserIds = User::query()
            ->where('is_active', true)
            ->pluck('id');

        if ($activeUserIds->isEmpty()) {
            Poll::query()
                ->whereIn('id', $expiredPolls->pluck('id'))
                ->update(['reminder_sent_at' => $now, 'updated_at' => $now]);

            return ['polls' => $expiredPolls->count(), 'notifications' => 0];
        }

        $notifications = [];

        /** @var Poll $poll */
        foreach ($expiredPolls as $poll) {
            foreach ($activeUserIds as $userId) {
                if ($poll->responses()->where('user_id', $userId)->exists()) {
                    continue;
                }

                $notifications[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'type' => 'poll_expired',
                    'notifiable_type' => Poll::class,
                    'notifiable_id' => $poll->id,
                    'title' => 'Encuesta expirada',
                    'message' => 'La encuesta "'.$poll->question.'" ha expirado.',
                    'data' => json_encode([
                        'poll_id' => $poll->id,
                        'expires_at' => $poll->expires_at?->toISOString(),
                    ], JSON_THROW_ON_ERROR),
                    'is_read' => false,
                    'read_at' => null,
                    'expires_at' => $now->copy()->addDays(7),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($notifications, $expiredPolls, $now): void {
            if (! empty($notifications)) {
                foreach (array_chunk($notifications, 500) as $chunk) {
                    Notification::query()->insert($chunk);
                }
            }

            Poll::query()
                ->whereIn('id', $expiredPolls->pluck('id'))
                ->update(['reminder_sent_at' => $now, 'updated_at' => $now]);
        });

        return [
            'polls' => $expiredPolls->count(),
            'notifications' => count($notifications),
        ];
    }
}
