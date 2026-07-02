<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\Poll;
use App\Src\Platform\Domain\ValueObjects\ContentStatus;
use App\Src\Platform\Infrastructure\Persistence\EloquentPoll;
use DateTimeImmutable;

final class PollMapper
{
    public static function toDomain(EloquentPoll $eloquent): Poll
    {
        return Poll::fromDatabase(
            id: $eloquent->id,
            question: $eloquent->question,
            options: $eloquent->options ?? [],
            isActive: (bool) $eloquent->is_active,
            status: new ContentStatus($eloquent->status ?? ContentStatus::Draft->value),
            expiresAt: $eloquent->expires_at ? new DateTimeImmutable($eloquent->expires_at) : null,
            scheduledAt: $eloquent->scheduled_at ? new DateTimeImmutable($eloquent->scheduled_at) : null,
            archiveAt: $eloquent->archive_at ? new DateTimeImmutable($eloquent->archive_at) : null,
            reminderSentAt: $eloquent->reminder_sent_at ? new DateTimeImmutable($eloquent->reminder_sent_at) : null,
            approvedBy: $eloquent->approved_by,
            approvedAt: $eloquent->approved_at ? new DateTimeImmutable($eloquent->approved_at) : null,
            moderationNotes: $eloquent->moderation_notes,
            versionHistory: $eloquent->version_history ?? [],
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(Poll $poll): array
    {
        return [
            'id' => $poll->id(),
            'question' => $poll->question(),
            'options' => $poll->options(),
            'is_active' => $poll->isActive(),
            'status' => $poll->status()->value,
            'expires_at' => $poll->expiresAt()?->format('Y-m-d H:i:s'),
            'scheduled_at' => $poll->scheduledAt()?->format('Y-m-d H:i:s'),
            'archive_at' => $poll->archiveAt()?->format('Y-m-d H:i:s'),
            'reminder_sent_at' => $poll->reminderSentAt()?->format('Y-m-d H:i:s'),
            'approved_by' => $poll->approvedBy(),
            'approved_at' => $poll->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $poll->moderationNotes(),
            'version_history' => $poll->versionHistory(),
            'created_at' => $poll->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $poll->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(Poll $poll): array
    {
        return [
            'question' => $poll->question(),
            'options' => $poll->options(),
            'is_active' => $poll->isActive(),
            'status' => $poll->status()->value,
            'expires_at' => $poll->expiresAt()?->format('Y-m-d H:i:s'),
            'scheduled_at' => $poll->scheduledAt()?->format('Y-m-d H:i:s'),
            'archive_at' => $poll->archiveAt()?->format('Y-m-d H:i:s'),
            'reminder_sent_at' => $poll->reminderSentAt()?->format('Y-m-d H:i:s'),
            'approved_by' => $poll->approvedBy(),
            'approved_at' => $poll->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $poll->moderationNotes(),
            'version_history' => $poll->versionHistory(),
        ];
    }
}
