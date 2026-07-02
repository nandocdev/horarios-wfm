<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\Shoutout;
use DateTimeImmutable;

interface ShoutoutRepositoryInterface {
    public function save(Shoutout $shoutout): Shoutout;

    public function findById(int $id): ?Shoutout;

    public function findAll(int $perPage = 25, array $filters = []): array;

    public function findPublished(int $perPage = 25): array;

    public function findPendingReview(int $perPage = 25): array;

    public function findByEmployee(int $employeeId, int $perPage = 25): array;

    public function countByStatus(string $status): int;

    public function delete(Shoutout $shoutout): void;

    public function archiveExpired(DateTimeImmutable $now): int;
}
