<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Repositories\ChannelRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class DeleteChannelHandler
{
    public function __construct(
        private ChannelRepositoryInterface $repository,
    ) {}

    public function handle(string $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new \InvalidArgumentException("Channel {$id} not found.");
        }

        $this->repository->delete($id);

        Log::info('Channel deleted.', [
            'channel_id' => $id,
        ]);
    }
}
