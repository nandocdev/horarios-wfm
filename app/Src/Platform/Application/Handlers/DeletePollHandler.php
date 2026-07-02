<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Repositories\PollRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class DeletePollHandler
{
    public function __construct(
        private PollRepositoryInterface $pollRepository,
    ) {}

    public function execute(int $pollId): void
    {
        DB::transaction(function () use ($pollId) {
            $poll = $this->pollRepository->findById($pollId);

            if (! $poll) {
                throw new \RuntimeException("Poll with ID {$pollId} not found.");
            }

            $this->pollRepository->delete($poll);
        });
    }
}
