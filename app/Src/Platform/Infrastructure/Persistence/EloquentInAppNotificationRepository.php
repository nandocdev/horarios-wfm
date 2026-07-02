<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\InAppNotificationMapper;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use DateTimeImmutable;

final class EloquentInAppNotificationRepository implements InAppNotificationRepositoryInterface {
    public function save(InAppNotification $notification): InAppNotification {
        $data = InAppNotificationMapper::toEloquent($notification);
        $data['id'] = $notification->id();
        $data['notifiable_type'] = $notification->notifiableType();
        $data['notifiable_id'] = $notification->notifiableId();

        $eloquent = EloquentInAppNotification::updateOrCreate(
            ['id' => $notification->id()],
            $data,
        );

        return InAppNotificationMapper::toDomain($eloquent);
    }

    public function findById(string $id): ?InAppNotification {
        $eloquent = EloquentInAppNotification::find($id);

        if ($eloquent === null) {
            return null;
        }

        return InAppNotificationMapper::toDomain($eloquent);
    }

    public function findByUser(int $userId, int $perPage = 25): array {
        $query = EloquentInAppNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentInAppNotification $eloquent) => InAppNotificationMapper::toDomain($eloquent))
            ->items();
    }

    public function findUnreadByUser(int $userId, int $perPage = 25): array {
        $query = EloquentInAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentInAppNotification $eloquent) => InAppNotificationMapper::toDomain($eloquent))
            ->items();
    }

    public function countUnreadByUser(int $userId): int {
        return EloquentInAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(string $id): void {
        EloquentInAppNotification::where('id', $id)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAllAsRead(int $userId): void {
        EloquentInAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function findByNotifiable(string $notifiableType, int $notifiableId): array {
        return EloquentInAppNotification::where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(EloquentInAppNotification $eloquent) => InAppNotificationMapper::toDomain($eloquent))
            ->toArray();
    }

    public function deleteExpired(): int {
        return EloquentInAppNotification::where('expires_at', '<=', now())
            ->orWhere(function ($q) {
                $q->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
            })
            ->delete();
    }

    public function delete(InAppNotification $notification): void {
        EloquentInAppNotification::where('id', $notification->id())->delete();
    }

    public function findUserIdsByTypeAndDate(string $type, DateTimeImmutable $date): array {
        $startOfDay = $date->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $endOfDay = $date->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return EloquentInAppNotification::where('type', $type)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->pluck('user_id')
            ->toArray();
    }
}
