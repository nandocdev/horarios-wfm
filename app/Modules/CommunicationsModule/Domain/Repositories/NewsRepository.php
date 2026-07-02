<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Repositories;

use App\Modules\CommunicationsModule\Domain\Aggregates\News;

interface NewsRepository
{
    public function save(News $news): void;

    public function findById(int $id): ?News;

    /** @return News[] */
    public function findScheduledToPublish(): array;

    /** @return News[] */
    public function findScheduledToArchive(): array;

    /** @return News[] */
    public function findPendingReview(): array;

    public function paginate(array $filters, int $perPage = 20, int $page = 1): array;

    public function count(array $filters): int;
}
