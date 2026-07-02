<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\ChannelDTO;
use App\Src\Connect\Domain\Entities\Channel;
use App\Src\Connect\Domain\Repositories\ChannelRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class UpdateChannelHandler
{
    public function __construct(
        private ChannelRepositoryInterface $repository,
    ) {}

    public function handle(string $id, ChannelDTO $dto): Channel
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new \InvalidArgumentException("Channel {$id} not found.");
        }

        $channel = new Channel(
            id: $id,
            name: $dto->name,
            type: $dto->type,
            isActive: $dto->isActive,
        );

        $saved = $this->repository->save($channel);

        Log::info('Channel updated.', [
            'channel_id' => $id,
            'name' => $dto->name,
        ]);

        return $saved;
    }
}
