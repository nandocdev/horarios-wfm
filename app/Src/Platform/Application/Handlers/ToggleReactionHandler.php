<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\ReactionDTO;
use App\Src\Platform\Domain\Entities\Reaction;
use App\Src\Platform\Domain\Events\ReactionAdded;
use App\Src\Platform\Domain\Events\ReactionRemoved;
use App\Src\Platform\Domain\Repositories\ReactionRepositoryInterface;
use App\Src\Platform\Domain\Repositories\ShoutoutRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class ToggleReactionHandler
{
    public function __construct(
        private ReactionRepositoryInterface $reactionRepository,
        private ShoutoutRepositoryInterface $shoutoutRepository,
    ) {}

    public function execute(ReactionDTO $dto, int $shoutoutId, int $userId): ?Reaction
    {
        return DB::transaction(function () use ($dto, $shoutoutId, $userId) {
            $shoutout = $this->shoutoutRepository->findById($shoutoutId);

            if (! $shoutout) {
                throw new \RuntimeException("Shoutout with ID {$shoutoutId} not found.");
            }

            $existing = $this->reactionRepository->findByUserAndType($shoutoutId, $userId, $dto->type);

            if ($existing) {
                $this->reactionRepository->delete($existing);
                event(new ReactionRemoved($existing));

                return null;
            }

            $reaction = Reaction::create(
                shoutoutId: $shoutoutId,
                userId: $userId,
                type: $dto->type,
                isActive: true,
            );

            $reaction = $this->reactionRepository->save($reaction);
            event(new ReactionAdded($reaction));

            return $reaction;
        });
    }
}
