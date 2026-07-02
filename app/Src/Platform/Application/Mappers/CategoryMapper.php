<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\Category;
use App\Src\Platform\Infrastructure\Persistence\EloquentCategory;
use DateTimeImmutable;

final class CategoryMapper
{
    public static function toDomain(EloquentCategory $eloquent): Category
    {
        return Category::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            slug: $eloquent->slug,
            description: $eloquent->description,
            color: $eloquent->color ?? '#3B82F6',
            isActive: (bool) $eloquent->is_active,
            sortOrder: (int) ($eloquent->sort_order ?? 0),
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(Category $category): array
    {
        return [
            'id' => $category->id(),
            'name' => $category->name(),
            'slug' => $category->slug(),
            'description' => $category->description(),
            'color' => $category->color(),
            'is_active' => $category->isActive(),
            'sort_order' => $category->sortOrder(),
            'created_at' => $category->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $category->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(Category $category): array
    {
        return [
            'name' => $category->name(),
            'slug' => $category->slug(),
            'description' => $category->description(),
            'color' => $category->color(),
            'is_active' => $category->isActive(),
            'sort_order' => $category->sortOrder(),
        ];
    }
}
