<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\ContentModeration;
use App\Src\Platform\Infrastructure\Persistence\EloquentContentModeration;
use DateTimeImmutable;

final class ContentModerationMapper
{
    public static function toDomain(EloquentContentModeration $eloquent): ContentModeration
    {
        return ContentModeration::fromDatabase(
            id: $eloquent->id,
            moderateableType: $eloquent->moderateable_type,
            moderateableId: $eloquent->moderateable_id,
            status: $eloquent->status,
            approvedBy: $eloquent->approved_by,
            approvedAt: $eloquent->approved_at ? new DateTimeImmutable($eloquent->approved_at) : null,
            moderationNotes: $eloquent->moderation_notes,
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(ContentModeration $moderation): array
    {
        return [
            'id' => $moderation->id(),
            'moderateable_type' => $moderation->moderateableType(),
            'moderateable_id' => $moderation->moderateableId(),
            'status' => $moderation->status(),
            'approved_by' => $moderation->approvedBy(),
            'approved_at' => $moderation->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $moderation->moderationNotes(),
            'created_at' => $moderation->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $moderation->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(ContentModeration $moderation): array
    {
        return [
            'moderateable_type' => $moderation->moderateableType(),
            'moderateable_id' => $moderation->moderateableId(),
            'status' => $moderation->status(),
            'approved_by' => $moderation->approvedBy(),
            'approved_at' => $moderation->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $moderation->moderationNotes(),
        ];
    }
}
