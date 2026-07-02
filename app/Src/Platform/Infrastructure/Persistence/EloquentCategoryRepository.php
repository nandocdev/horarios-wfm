<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\CategoryMapper;
use App\Src\Platform\Domain\Entities\Category;
use App\Src\Platform\Domain\Repositories\CategoryRepositoryInterface;

final class EloquentCategoryRepository implements CategoryRepositoryInterface {
    public function save(Category $category): Category {
        $eloquent = EloquentCategory::updateOrCreate(
            ['id' => $category->id()],
            CategoryMapper::toEloquent($category),
        );

        return CategoryMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?Category {
        $eloquent = EloquentCategory::find($id);

        if ($eloquent === null) {
            return null;
        }

        return CategoryMapper::toDomain($eloquent);
    }

    public function findBySlug(string $slug): ?Category {
        $eloquent = EloquentCategory::where('slug', $slug)->first();

        if ($eloquent === null) {
            return null;
        }

        return CategoryMapper::toDomain($eloquent);
    }

    public function findAll(bool $includeInactive = false): array {
        $query = EloquentCategory::orderBy('sort_order', 'asc');

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get()
            ->map(fn(EloquentCategory $eloquent) => CategoryMapper::toDomain($eloquent))
            ->toArray();
    }

    public function findActive(): array {
        return EloquentCategory::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn(EloquentCategory $eloquent) => CategoryMapper::toDomain($eloquent))
            ->toArray();
    }

    public function findByName(string $name): ?Category {
        $eloquent = EloquentCategory::where('name', $name)->first();

        if ($eloquent === null) {
            return null;
        }

        return CategoryMapper::toDomain($eloquent);
    }

    public function reorder(int $id, int $newSortOrder): void {
        EloquentCategory::where('id', $id)->update(['sort_order' => $newSortOrder]);
    }

    public function delete(Category $category): void {
        EloquentCategory::where('id', $category->id())->delete();
    }
}
