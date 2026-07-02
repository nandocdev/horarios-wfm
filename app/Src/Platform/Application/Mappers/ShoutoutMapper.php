<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\Shoutout;
use App\Src\Platform\Domain\ValueObjects\ContentStatus;
use App\Src\Platform\Infrastructure\Persistence\EloquentShoutout;
use DateTimeImmutable;

final class ShoutoutMapper
{
    public static function toDomain(EloquentShoutout $eloquent): Shoutout
    {
        return Shoutout::fromDatabase(
            id: $eloquent->id,
            employeeId: $eloquent->employee_id,
            message: $eloquent->message,
            isActive: (bool) $eloquent->is_active,
            status: new ContentStatus($eloquent->status ?? ContentStatus::Draft->value),
            scheduledAt: $eloquent->scheduled_at ? new DateTimeImmutable($eloquent->scheduled_at) : null,
            archiveAt: $eloquent->archive_at ? new DateTimeImmutable($eloquent->archive_at) : null,
            approvedBy: $eloquent->approved_by,
            approvedAt: $eloquent->approved_at ? new DateTimeImmutable($eloquent->approved_at) : null,
            moderationNotes: $eloquent->moderation_notes,
            versionHistory: $eloquent->version_history ?? [],
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(Shoutout $shoutout): array
    {
        return [
            'id' => $shoutout->id(),
            'employee_id' => $shoutout->employeeId(),
            'message' => $shoutout->message(),
            'is_active' => $shoutout->isActive(),
            'status' => $shoutout->status()->value,
            'scheduled_at' => $shoutout->scheduledAt()?->format('Y-m-d H:i:s'),
            'archive_at' => $shoutout->archiveAt()?->format('Y-m-d H:i:s'),
            'approved_by' => $shoutout->approvedBy(),
            'approved_at' => $shoutout->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $shoutout->moderationNotes(),
            'version_history' => $shoutout->versionHistory(),
            'created_at' => $shoutout->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $shoutout->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(Shoutout $shoutout): array
    {
        return [
            'employee_id' => $shoutout->employeeId(),
            'message' => $shoutout->message(),
            'is_active' => $shoutout->isActive(),
            'status' => $shoutout->status()->value,
            'scheduled_at' => $shoutout->scheduledAt()?->format('Y-m-d H:i:s'),
            'archive_at' => $shoutout->archiveAt()?->format('Y-m-d H:i:s'),
            'approved_by' => $shoutout->approvedBy(),
            'approved_at' => $shoutout->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $shoutout->moderationNotes(),
            'version_history' => $shoutout->versionHistory(),
        ];
    }
}
