<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\RecordVote;

use App\Modules\CommunicationsModule\Domain\Repositories\PollRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use RuntimeException;

final readonly class Handler
{
    public function __construct(
        private PollRepository $repository,
    ) {}

    public function __invoke(Command $command): void
    {
        $poll = $this->repository->findById($command->pollId);

        if ($poll === null) {
            throw new RuntimeException("Poll not found: {$command->pollId}");
        }

        $poll->recordVote(
            userId: new PersonId($command->userId),
            answer: $command->answer,
        );

        $this->repository->save($poll);
    }
}
