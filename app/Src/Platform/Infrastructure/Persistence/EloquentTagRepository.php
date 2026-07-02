<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\TagMapper;
use App\Src\Platform\Domain\Entities\Tag;
use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;

final class EloquentTagRepository implements TagRepositoryInterface {
    public function save(Tag $tag): Tag {
        $eloquent = EloquentTag::updateOrCreate(
            ['id' => $tag->id()],
            TagMapper::toEloquent($tag),
        );

        return TagMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?Tag {
        $eloquent = EloquentTag::find($id);

        if ($eloquent === null) {
            return null;
        }

        return TagMapper::toDomain($eloquent);
    }

    public function findBySlug(string $slug): ?Tag {
        $eloquent = EloquentTag::where('slug', $slug)->first();

        if ($eloquent === null) {
            return null;
        }

        return TagMapper::toDomain($eloquent);
    }

    public function findAll(bool $includeInactive = false): array {
        $query = EloquentTag::orderBy('name', 'asc');

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get()
            ->map(fn(EloquentTag $eloquent) => TagMapper::toDomain($eloquent))
            ->toArray();
    }

    public function findActive(): array {
        return EloquentTag::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn(EloquentTag $eloquent) => TagMapper::toDomain($eloquent))
            ->toArray();
    }

    public function findByName(string $name): ?Tag {
        $eloquent = EloquentTag::where('name', $name)->first();

        if ($eloquent === null) {
            return null;
        }

        return TagMapper::toDomain($eloquent);
    }

    public function delete(Tag $tag): void {
        EloquentTag::where('id', $tag->id())->delete();
    }
}
