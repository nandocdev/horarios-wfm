<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\CreateShoutout;

use App\Modules\CommunicationsModule\Domain\Aggregates\Shoutout;
use App\Modules\CommunicationsModule\Domain\Repositories\ShoutoutRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ShoutoutMessage;
use DateTimeImmutable;

final readonly class Handler
{
    public function __construct(
        private ShoutoutRepository $repository,
    ) {}

    public function __invoke(Command $command): Shoutout
    {
        $scheduledAt = $command->scheduledAt ? new DateTimeImmutable($command->scheduledAt) : null;
        $archiveAt = $command->archiveAt ? new DateTimeImmutable($command->archiveAt) : null;

        $message = new ShoutoutMessage(
            message: new ContentBody($command->message),
            employeeId: new PersonId($command->employeeId),
        );

        $shoutout = $command->workflowAction === 'submit_review'
            ? Shoutout::submitForReview($message, new PersonId($command->authorId), new DateRange($scheduledAt, $archiveAt), $command->isActive)
            : Shoutout::draft($message, new PersonId($command->authorId), new DateRange($scheduledAt, $archiveAt), $command->isActive);

        $this->repository->save($shoutout);

        return $shoutout;
    }
}
