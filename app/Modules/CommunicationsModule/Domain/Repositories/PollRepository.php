<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Repositories;

use App\Modules\CommunicationsModule\Domain\Aggregates\Poll;

interface PollRepository
{
    public function save(Poll $poll): void;

    public function findById(int $id): ?Poll;

    /** @return Poll[] */
    public function findActive(): array;

    /** @return Poll[] */
    public function findExpired(): array;

    /** @return Poll[] */
    public function findExpiringWithoutReminder(): array;

    public function paginate(array $filters, int $perPage = 20, int $page = 1): array;
}
