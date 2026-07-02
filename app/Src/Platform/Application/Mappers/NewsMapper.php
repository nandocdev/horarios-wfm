<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\News;
use App\Src\Platform\Domain\ValueObjects\ContentStatus;
use App\Src\Platform\Infrastructure\Persistence\EloquentNews;
use DateTimeImmutable;

final class NewsMapper
{
    public static function toDomain(EloquentNews $eloquent): News
    {
        return News::fromDatabase(
            id: $eloquent->id,
            title: $eloquent->title,
            slug: $eloquent->slug,
            excerpt: $eloquent->excerpt,
            content: $eloquent->content,
            authorId: $eloquent->author_id,
            isActive: (bool) $eloquent->is_active,
            status: new ContentStatus($eloquent->status ?? ContentStatus::Draft->value),
            publishedAt: $eloquent->published_at ? new DateTimeImmutable($eloquent->published_at) : null,
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

    public static function toArray(News $news): array
    {
        return [
            'id' => $news->id(),
            'title' => $news->title(),
            'slug' => $news->slug(),
            'excerpt' => $news->excerpt(),
            'content' => $news->content(),
            'author_id' => $news->authorId(),
            'is_active' => $news->isActive(),
            'status' => $news->status()->value,
            'published_at' => $news->publishedAt()?->format('Y-m-d H:i:s'),
            'scheduled_at' => $news->scheduledAt()?->format('Y-m-d H:i:s'),
            'archive_at' => $news->archiveAt()?->format('Y-m-d H:i:s'),
            'approved_by' => $news->approvedBy(),
            'approved_at' => $news->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $news->moderationNotes(),
            'version_history' => $news->versionHistory(),
            'created_at' => $news->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $news->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(News $news): array
    {
        return [
            'title' => $news->title(),
            'slug' => $news->slug(),
            'excerpt' => $news->excerpt(),
            'content' => $news->content(),
            'author_id' => $news->authorId(),
            'is_active' => $news->isActive(),
            'status' => $news->status()->value,
            'published_at' => $news->publishedAt()?->format('Y-m-d H:i:s'),
            'scheduled_at' => $news->scheduledAt()?->format('Y-m-d H:i:s'),
            'archive_at' => $news->archiveAt()?->format('Y-m-d H:i:s'),
            'approved_by' => $news->approvedBy(),
            'approved_at' => $news->approvedAt()?->format('Y-m-d H:i:s'),
            'moderation_notes' => $news->moderationNotes(),
            'version_history' => $news->versionHistory(),
        ];
    }
}
