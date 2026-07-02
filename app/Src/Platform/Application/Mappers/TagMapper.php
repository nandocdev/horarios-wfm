<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\Tag;
use App\Src\Platform\Infrastructure\Persistence\EloquentTag;
use DateTimeImmutable;

final class TagMapper
{
    public static function toDomain(EloquentTag $eloquent): Tag
    {
        return Tag::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            slug: $eloquent->slug,
            color: $eloquent->color ?? '#6B7280',
            isActive: (bool) $eloquent->is_active,
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(Tag $tag): array
    {
        return [
            'id' => $tag->id(),
            'name' => $tag->name(),
            'slug' => $tag->slug(),
            'color' => $tag->color(),
            'is_active' => $tag->isActive(),
            'created_at' => $tag->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $tag->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(Tag $tag): array
    {
        return [
            'name' => $tag->name(),
            'slug' => $tag->slug(),
            'color' => $tag->color(),
            'is_active' => $tag->isActive(),
        ];
    }
}
