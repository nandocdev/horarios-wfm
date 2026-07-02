<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\CreatePoll;

use App\Modules\CommunicationsModule\Domain\Aggregates\Poll;
use App\Modules\CommunicationsModule\Domain\Repositories\PollRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PollOption;
use DateTimeImmutable;

final readonly class Handler
{
    public function __construct(
        private PollRepository $repository,
    ) {}

    public function __invoke(Command $command): Poll
    {
        $options = array_map(
            fn (array $opt) => PollOption::fromArray($opt),
            $command->options,
        );

        $scheduledAt = $command->scheduledAt ? new DateTimeImmutable($command->scheduledAt) : null;
        $expiresAt = $command->expiresAt ? new DateTimeImmutable($command->expiresAt) : null;

        $poll = $command->workflowAction === 'submit_review'
            ? Poll::submitForReview($command->question, $options, new DateRange($scheduledAt, $expiresAt), $command->isActive)
            : Poll::draft($command->question, $options, new DateRange($scheduledAt, $expiresAt), $command->isActive);

        $this->repository->save($poll);

        return $poll;
    }
}
