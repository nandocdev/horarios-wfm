<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\Tag;

interface TagRepositoryInterface {
    public function save(Tag $tag): Tag;

    public function findById(int $id): ?Tag;

    public function findBySlug(string $slug): ?Tag;

    public function findAll(bool $includeInactive = false): array;

    public function findActive(): array;

    public function findByName(string $name): ?Tag;

    public function delete(Tag $tag): void;
}
