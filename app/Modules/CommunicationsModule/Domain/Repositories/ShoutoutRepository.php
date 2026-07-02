<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Repositories;

use App\Modules\CommunicationsModule\Domain\Aggregates\Shoutout;

interface ShoutoutRepository
{
    public function save(Shoutout $shoutout): void;

    public function findById(int $id): ?Shoutout;

    /** @return Shoutout[] */
    public function findActive(int $limit = 6): array;

    /** @return Shoutout[] */
    public function findScheduledToArchive(): array;

    public function paginate(array $filters, int $perPage = 20, int $page = 1): array;
}
