<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\ShoutoutMapper;
use App\Src\Platform\Domain\Entities\Shoutout;
use App\Src\Platform\Domain\Repositories\ShoutoutRepositoryInterface;
use DateTimeImmutable;

final class EloquentShoutoutRepository implements ShoutoutRepositoryInterface {
    public function save(Shoutout $shoutout): Shoutout {
        $eloquent = EloquentShoutout::updateOrCreate(
            ['id' => $shoutout->id()],
            ShoutoutMapper::toEloquent($shoutout),
        );

        return ShoutoutMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?Shoutout {
        $eloquent = EloquentShoutout::with(['categories', 'tags', 'reactions'])->find($id);

        if ($eloquent === null) {
            return null;
        }

        return ShoutoutMapper::toDomain($eloquent);
    }

    public function findAll(int $perPage = 25, array $filters = []): array {
        $query = EloquentShoutout::with(['categories', 'tags', 'reactions']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('message', 'ilike', "%{$search}%");
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentShoutout $eloquent) => ShoutoutMapper::toDomain($eloquent))
            ->items();
    }

    public function findPublished(int $perPage = 25): array {
        $query = EloquentShoutout::with(['categories', 'tags', 'reactions'])
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentShoutout $eloquent) => ShoutoutMapper::toDomain($eloquent))
            ->items();
    }

    public function findPendingReview(int $perPage = 25): array {
        $query = EloquentShoutout::with(['categories', 'tags', 'reactions'])
            ->where('status', 'pending_review')
            ->orderBy('created_at', 'asc');

        return $query->paginate($perPage)
            ->through(fn(EloquentShoutout $eloquent) => ShoutoutMapper::toDomain($eloquent))
            ->items();
    }

    public function findByEmployee(int $employeeId, int $perPage = 25): array {
        $query = EloquentShoutout::with(['categories', 'tags', 'reactions'])
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentShoutout $eloquent) => ShoutoutMapper::toDomain($eloquent))
            ->items();
    }

    public function countByStatus(string $status): int {
        return EloquentShoutout::where('status', $status)->count();
    }

    public function delete(Shoutout $shoutout): void {
        EloquentShoutout::where('id', $shoutout->id())->delete();
    }

    public function archiveExpired(DateTimeImmutable $now): int {
        return EloquentShoutout::where('status', 'published')
            ->where('archive_at', '<=', $now->format('Y-m-d H:i:s'))
            ->update(['status' => 'archived']);
    }
}
