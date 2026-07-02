<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Infrastructure\Persistence\EloquentInAppNotification;
use DateTimeImmutable;

final class InAppNotificationMapper
{
    public static function toDomain(EloquentInAppNotification $eloquent): InAppNotification
    {
        return InAppNotification::fromDatabase(
            id: $eloquent->id,
            userId: (int) $eloquent->user_id,
            type: $eloquent->type,
            notifiableType: $eloquent->notifiable_type,
            notifiableId: (int) $eloquent->notifiable_id,
            title: $eloquent->title,
            message: $eloquent->message,
            data: $eloquent->data ?? [],
            isRead: (bool) $eloquent->is_read,
            readAt: $eloquent->read_at ? new DateTimeImmutable($eloquent->read_at) : null,
            expiresAt: $eloquent->expires_at ? new DateTimeImmutable($eloquent->expires_at) : null,
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(InAppNotification $notification): array
    {
        return [
            'id' => $notification->id(),
            'user_id' => $notification->userId(),
            'type' => $notification->type(),
            'notifiable_type' => $notification->notifiableType(),
            'notifiable_id' => $notification->notifiableId(),
            'title' => $notification->title(),
            'message' => $notification->message(),
            'data' => $notification->data(),
            'is_read' => $notification->isRead(),
            'read_at' => $notification->readAt()?->format('Y-m-d H:i:s'),
            'expires_at' => $notification->expiresAt()?->format('Y-m-d H:i:s'),
            'created_at' => $notification->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $notification->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(InAppNotification $notification): array
    {
        return [
            'user_id' => $notification->userId(),
            'type' => $notification->type(),
            'notifiable_type' => $notification->notifiableType(),
            'notifiable_id' => $notification->notifiableId(),
            'title' => $notification->title(),
            'message' => $notification->message(),
            'data' => $notification->data(),
            'is_read' => $notification->isRead(),
            'read_at' => $notification->readAt()?->format('Y-m-d H:i:s'),
            'expires_at' => $notification->expiresAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
