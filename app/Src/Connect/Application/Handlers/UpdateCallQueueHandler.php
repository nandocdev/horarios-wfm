<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CallQueueDTO;
use App\Src\Connect\Domain\Entities\CallQueue;
use App\Src\Connect\Domain\Repositories\CallQueueRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class UpdateCallQueueHandler
{
    public function __construct(
        private CallQueueRepositoryInterface $repository,
    ) {}

    public function handle(int $id, CallQueueDTO $dto): CallQueue
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new \InvalidArgumentException("Call queue {$id} not found.");
        }

        $queue = new CallQueue(
            id: $id,
            name: $dto->name,
            description: $dto->description,
            extension: $dto->extension,
            isActive: $dto->isActive,
        );

        $saved = $this->repository->save($queue);

        Log::info('Call queue updated.', [
            'queue_id' => $id,
            'name' => $dto->name,
        ]);

        return $saved;
    }
}
