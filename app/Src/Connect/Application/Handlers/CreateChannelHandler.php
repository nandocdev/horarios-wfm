<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\ChannelDTO;
use App\Src\Connect\Domain\Entities\Channel;
use App\Src\Connect\Domain\Repositories\ChannelRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CreateChannelHandler
{
    public function __construct(
        private ChannelRepositoryInterface $repository,
    ) {}

    public function handle(ChannelDTO $dto): Channel
    {
        $channel = new Channel(
            id: (string) Str::ulid(),
            name: $dto->name,
            type: $dto->type,
            isActive: $dto->isActive,
        );

        $saved = $this->repository->save($channel);

        Log::info('Channel created.', [
            'channel_id' => $saved->id(),
            'name' => $dto->name,
        ]);

        return $saved;
    }
}
