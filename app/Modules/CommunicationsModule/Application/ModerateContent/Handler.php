<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\ModerateContent;

use App\Modules\CommunicationsModule\Domain\Repositories\NewsRepository;
use App\Modules\CommunicationsModule\Domain\Repositories\ShoutoutRepository;
use App\Modules\CommunicationsModule\Domain\Repositories\PollRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ModerationDecision;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use RuntimeException;

final readonly class Handler
{
    public function __construct(
        private NewsRepository $newsRepository,
        private ShoutoutRepository $shoutoutRepository,
        private PollRepository $pollRepository,
    ) {}

    public function __invoke(Command $command): void
    {
        $decision = new ModerationDecision(
            action: $command->action,
            moderatorId: new PersonId($command->moderatorId),
            notes: $command->notes,
        );

        $content = match ($command->contentType) {
            'news' => $this->newsRepository->findById($command->contentId),
            'shoutout' => $this->shoutoutRepository->findById($command->contentId),
            'poll' => $this->pollRepository->findById($command->contentId),
            default => throw new RuntimeException("Unknown content type: {$command->contentType}"),
        };

        if ($content === null) {
            throw new RuntimeException("{$command->contentType} not found: {$command->contentId}");
        }

        if ($command->action === 'archive') {
            $content->archive();
        } else {
            $content->applyModeration($decision);
        }

        match ($command->contentType) {
            'news' => $this->newsRepository->save($content),
            'shoutout' => $this->shoutoutRepository->save($content),
            'poll' => $this->pollRepository->save($content),
        };
    }
}
