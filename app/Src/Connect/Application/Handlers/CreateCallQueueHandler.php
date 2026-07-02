<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CallQueueDTO;
use App\Src\Connect\Domain\Entities\CallQueue;
use App\Src\Connect\Domain\Repositories\CallQueueRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class CreateCallQueueHandler
{
    public function __construct(
        private CallQueueRepositoryInterface $repository,
    ) {}

    public function handle(CallQueueDTO $dto): CallQueue
    {
        $queue = new CallQueue(
            id: null,
            name: $dto->name,
            description: $dto->description,
            extension: $dto->extension,
            isActive: $dto->isActive,
        );

        $saved = $this->repository->save($queue);

        Log::info('Call queue created.', [
            'queue_id' => $saved->id(),
            'name' => $dto->name,
        ]);

        return $saved;
    }
}
