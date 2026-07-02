<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\Mention;
use App\Src\Platform\Infrastructure\Persistence\EloquentMention;
use DateTimeImmutable;

final class MentionMapper
{
    public static function toDomain(EloquentMention $eloquent): Mention
    {
        return Mention::fromDatabase(
            id: $eloquent->id,
            mentionedUserId: $eloquent->mentioned_user_id,
            mentionerUserId: $eloquent->mentioner_user_id,
            mentionableType: $eloquent->mentionable_type,
            mentionableId: $eloquent->mentionable_id,
            context: $eloquent->context,
            isRead: (bool) $eloquent->is_read,
            readAt: $eloquent->read_at ? new DateTimeImmutable($eloquent->read_at) : null,
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(Mention $mention): array
    {
        return [
            'id' => $mention->id(),
            'mentioned_user_id' => $mention->mentionedUserId(),
            'mentioner_user_id' => $mention->mentionerUserId(),
            'mentionable_type' => $mention->mentionableType(),
            'mentionable_id' => $mention->mentionableId(),
            'context' => $mention->context(),
            'is_read' => $mention->isRead(),
            'read_at' => $mention->readAt()?->format('Y-m-d H:i:s'),
            'created_at' => $mention->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $mention->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(Mention $mention): array
    {
        return [
            'mentioned_user_id' => $mention->mentionedUserId(),
            'mentioner_user_id' => $mention->mentionerUserId(),
            'mentionable_type' => $mention->mentionableType(),
            'mentionable_id' => $mention->mentionableId(),
            'context' => $mention->context(),
            'is_read' => $mention->isRead(),
            'read_at' => $mention->readAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
