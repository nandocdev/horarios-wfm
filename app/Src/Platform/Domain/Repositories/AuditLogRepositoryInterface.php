<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\AuditLog;
use DateTimeImmutable;

interface AuditLogRepositoryInterface {
    public function save(AuditLog $auditLog): AuditLog;

    public function findById(int $id): ?AuditLog;

    public function search(array $filters, int $perPage = 25): array;

    public function pruneOlderThan(DateTimeImmutable $cutoff, int $chunkSize = 500): int;

    public function countOlderThan(DateTimeImmutable $cutoff): int;
}
