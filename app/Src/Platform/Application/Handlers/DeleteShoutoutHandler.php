<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Repositories\ShoutoutRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class DeleteShoutoutHandler
{
    public function __construct(
        private ShoutoutRepositoryInterface $shoutoutRepository,
    ) {}

    public function execute(int $shoutoutId): void
    {
        DB::transaction(function () use ($shoutoutId) {
            $shoutout = $this->shoutoutRepository->findById($shoutoutId);

            if (! $shoutout) {
                throw new \RuntimeException("Shoutout with ID {$shoutoutId} not found.");
            }

            $this->shoutoutRepository->delete($shoutout);
        });
    }
}
