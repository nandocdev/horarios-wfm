<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Application\Mappers;

use App\Src\Knowledge\Domain\Entities\Article;
use App\Src\Knowledge\Domain\Entities\Category;
use App\Src\Knowledge\Infrastructure\Persistence\EloquentArticle;
use App\Src\Knowledge\Infrastructure\Persistence\EloquentCategory;
use DateTimeImmutable;

final class KnowledgeMapper
{
    public static function articleToDomain(EloquentArticle $e): Article
    {
        $tagNames = $e->relationLoaded('tags')
            ? $e->tags->pluck('name')->toArray()
            : [];

        $queueIds = $e->relationLoaded('queues')
            ? $e->queues->pluck('id')->toArray()
            : [];

        return new Article(
            id: $e->id,
            title: $e->title,
            slug: $e->slug,
            summary: $e->summary,
            content: $e->content,
            categoryId: $e->category_id,
            status: $e->status ?? Article::STATUS_DRAFT,
            version: (int) ($e->version ?? 1),
            publishedAt: $e->published_at ? self::toImmutable($e->published_at) : null,
            expiresAt: $e->expires_at ? self::toImmutable($e->expires_at) : null,
            createdBy: $e->created_by,
            updatedBy: $e->updated_by,
            tagNames: $tagNames,
            queueIds: $queueIds,
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
        );
    }

    public static function categoryToDomain(EloquentCategory $e): Category
    {
        return new Category(
            id: $e->id,
            name: $e->name,
            description: $e->description,
        );
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
