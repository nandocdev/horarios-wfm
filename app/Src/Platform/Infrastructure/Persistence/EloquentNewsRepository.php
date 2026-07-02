<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\NewsMapper;
use App\Src\Platform\Domain\Entities\News;
use App\Src\Platform\Domain\Repositories\NewsRepositoryInterface;
use DateTimeImmutable;

final class EloquentNewsRepository implements NewsRepositoryInterface {
    public function save(News $news): News {
        $eloquent = EloquentNews::updateOrCreate(
            ['id' => $news->id()],
            NewsMapper::toEloquent($news),
        );

        return NewsMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?News {
        $eloquent = EloquentNews::with(['author', 'categories', 'tags'])->find($id);

        if ($eloquent === null) {
            return null;
        }

        return NewsMapper::toDomain($eloquent);
    }

    public function findBySlug(string $slug): ?News {
        $eloquent = EloquentNews::with(['author', 'categories', 'tags'])
            ->where('slug', $slug)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return NewsMapper::toDomain($eloquent);
    }

    public function findAll(int $perPage = 25, array $filters = []): array {
        $query = EloquentNews::with(['author', 'categories', 'tags']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $filters['category_id']));
        }

        $query->orderBy('created_at', 'desc');

        return $this->paginateAndMap($query, $perPage);
    }

    public function findPublished(int $perPage = 25): array {
        $query = EloquentNews::with(['author', 'categories', 'tags'])
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderBy('published_at', 'desc');

        return $this->paginateAndMap($query, $perPage);
    }

    public function findPendingReview(int $perPage = 25): array {
        $query = EloquentNews::with(['author', 'categories', 'tags'])
            ->where('status', 'pending_review')
            ->orderBy('created_at', 'asc');

        return $this->paginateAndMap($query, $perPage);
    }

    public function findByAuthor(int $authorId, int $perPage = 25): array {
        $query = EloquentNews::with(['author', 'categories', 'tags'])
            ->where('author_id', $authorId)
            ->orderBy('created_at', 'desc');

        return $this->paginateAndMap($query, $perPage);
    }

    public function search(string $query, int $perPage = 25): array {
        $eloquentQuery = EloquentNews::with(['author', 'categories', 'tags'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'ilike', "%{$query}%")
                    ->orWhere('content', 'ilike', "%{$query}%")
                    ->orWhere('excerpt', 'ilike', "%{$query}%");
            })
            ->orderBy('created_at', 'desc');

        return $this->paginateAndMap($eloquentQuery, $perPage);
    }

    public function countByStatus(string $status): int {
        return EloquentNews::where('status', $status)->count();
    }

    public function delete(News $news): void {
        EloquentNews::where('id', $news->id())->delete();
    }

    public function archiveExpired(DateTimeImmutable $now): int {
        return EloquentNews::where('status', 'published')
            ->where('archive_at', '<=', $now->format('Y-m-d H:i:s'))
            ->update(['status' => 'archived']);
    }

    public function publishScheduled(DateTimeImmutable $now): int {
        return EloquentNews::where('status', 'draft')
            ->where('scheduled_at', '<=', $now->format('Y-m-d H:i:s'))
            ->whereNotNull('scheduled_at')
            ->update([
                'status' => 'published',
                'published_at' => $now->format('Y-m-d H:i:s'),
            ]);
    }

    public function findPublishedToday(DateTimeImmutable $now): array {
        $startOfDay = $now->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $endOfDay = $now->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return EloquentNews::with(['author', 'categories', 'tags'])
            ->where('status', 'published')
            ->whereBetween('published_at', [$startOfDay, $endOfDay])
            ->orderBy('published_at', 'desc')
            ->get()
            ->map(fn(EloquentNews $eloquent) => NewsMapper::toDomain($eloquent))
            ->toArray();
    }

    private function paginateAndMap($query, int $perPage): array {
        return $query->paginate($perPage)
            ->through(fn(EloquentNews $eloquent) => NewsMapper::toDomain($eloquent))
            ->items();
    }
}
