<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\ToggleReaction;

use App\Modules\CommunicationsModule\Domain\Enums\ReactionType;
use App\Modules\CommunicationsModule\Domain\Repositories\ShoutoutRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use RuntimeException;

final readonly class Handler
{
    public function __construct(
        private ShoutoutRepository $repository,
    ) {}

    public function __invoke(Command $command): void
    {
        $shoutout = $this->repository->findById($command->shoutoutId);

        if ($shoutout === null) {
            throw new RuntimeException("Shoutout not found: {$command->shoutoutId}");
        }

        $shoutout->toggleReaction(
            userId: new PersonId($command->userId),
            type: ReactionType::from($command->type),
        );

        $this->repository->save($shoutout);
    }
}
