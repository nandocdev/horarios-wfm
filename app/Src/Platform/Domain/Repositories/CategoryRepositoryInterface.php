<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\Category;

interface CategoryRepositoryInterface {
    public function save(Category $category): Category;

    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function findAll(bool $includeInactive = false): array;

    public function findActive(): array;

    public function findByName(string $name): ?Category;

    public function reorder(int $id, int $newSortOrder): void;

    public function delete(Category $category): void;
}
