<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\Comment;
use App\Src\Platform\Infrastructure\Persistence\EloquentComment;
use DateTimeImmutable;

final class CommentMapper
{
    public static function toDomain(EloquentComment $eloquent): Comment
    {
        return Comment::fromDatabase(
            id: $eloquent->id,
            newsId: $eloquent->news_id,
            userId: $eloquent->user_id,
            content: $eloquent->content,
            parentId: $eloquent->parent_id,
            isActive: (bool) $eloquent->is_active,
            publishedAt: $eloquent->published_at ? new DateTimeImmutable($eloquent->published_at) : null,
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(Comment $comment): array
    {
        return [
            'id' => $comment->id(),
            'news_id' => $comment->newsId(),
            'user_id' => $comment->userId(),
            'content' => $comment->content(),
            'parent_id' => $comment->parentId(),
            'is_active' => $comment->isActive(),
            'published_at' => $comment->publishedAt()?->format('Y-m-d H:i:s'),
            'created_at' => $comment->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $comment->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(Comment $comment): array
    {
        return [
            'news_id' => $comment->newsId(),
            'user_id' => $comment->userId(),
            'content' => $comment->content(),
            'parent_id' => $comment->parentId(),
            'is_active' => $comment->isActive(),
            'published_at' => $comment->publishedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
