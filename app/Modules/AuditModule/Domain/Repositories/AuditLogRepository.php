<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\Repositories;

use App\Modules\AuditModule\Domain\Aggregates\AuditLogEntry;
use DateTimeImmutable;

interface AuditLogRepository
{
    public function save(AuditLogEntry $entry): void;

    public function findById(int $id): ?AuditLogEntry;

    /** @return array{items: AuditLogEntry[], total: int, perPage: int, page: int} */
    public function paginate(array $filters, int $perPage = 20, int $page = 1): array;

    public function count(array $filters): int;

    public function deleteOlderThan(DateTimeImmutable $cutoff, int $chunkSize = 500): int;

    public function countOlderThan(DateTimeImmutable $cutoff): int;
}
