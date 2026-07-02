<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\News;
use DateTimeImmutable;

interface NewsRepositoryInterface {
    public function save(News $news): News;

    public function findById(int $id): ?News;

    public function findBySlug(string $slug): ?News;

    public function findAll(int $perPage = 25, array $filters = []): array;

    public function findPublished(int $perPage = 25): array;

    public function findPendingReview(int $perPage = 25): array;

    public function findByAuthor(int $authorId, int $perPage = 25): array;

    public function search(string $query, int $perPage = 25): array;

    public function countByStatus(string $status): int;

    public function delete(News $news): void;

    public function archiveExpired(DateTimeImmutable $now): int;

    public function publishScheduled(DateTimeImmutable $now): int;

    public function findPublishedToday(DateTimeImmutable $now): array;
}
