<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Repositories\CallQueueRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class DeleteCallQueueHandler
{
    public function __construct(
        private CallQueueRepositoryInterface $repository,
    ) {}

    public function handle(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new \InvalidArgumentException("Call queue {$id} not found.");
        }

        $this->repository->delete($id);

        Log::info('Call queue deleted.', [
            'queue_id' => $id,
        ]);
    }
}
