<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\PruneAuditLogs;

use App\Modules\AuditModule\Domain\Repositories\AuditLogRepository;
use DateTimeImmutable;

final readonly class Handler
{
    public function __construct(
        private AuditLogRepository $repository,
    ) {}

    public function __invoke(Command $command): Result
    {
        $cutoff = new DateTimeImmutable("-{$command->days} days");

        $count = $this->repository->countOlderThan($cutoff);

        if ($command->dryRun || $count === 0) {
            return new Result($count, $cutoff, $command->dryRun);
        }

        $deleted = $this->repository->deleteOlderThan($cutoff, $command->chunkSize);

        return new Result($deleted, $cutoff);
    }
}
