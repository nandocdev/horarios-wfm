<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Services;

use App\Modules\CommunicationsModule\Domain\Repositories\NewsRepository;
use App\Modules\CommunicationsModule\Domain\Repositories\PollRepository;
use App\Modules\CommunicationsModule\Domain\Repositories\ShoutoutRepository;
use DateTimeImmutable;

final class ContentScheduler
{
    public function __construct(
        private NewsRepository $newsRepository,
        private ShoutoutRepository $shoutoutRepository,
        private PollRepository $pollRepository,
    ) {}

    public function publishScheduled(): array
    {
        $published = ['news' => 0, 'polls' => 0, 'shoutouts' => 0];

        foreach ($this->newsRepository->findScheduledToPublish() as $news) {
            $news->publish();
            $this->newsRepository->save($news);
            $published['news']++;
        }

        return $published;
    }

    public function archiveExpired(): array
    {
        $archived = ['news' => 0, 'polls' => 0, 'shoutouts' => 0];

        foreach ($this->newsRepository->findScheduledToArchive() as $news) {
            $news->archive();
            $this->newsRepository->save($news);
            $archived['news']++;
        }

        foreach ($this->shoutoutRepository->findScheduledToArchive() as $shoutout) {
            $shoutout->archive();
            $this->shoutoutRepository->save($shoutout);
            $archived['shoutouts']++;
        }

        foreach ($this->pollRepository->findExpired() as $poll) {
            $poll->markExpired();
            $this->pollRepository->save($poll);
            $archived['polls']++;
        }

        return $archived;
    }

    public function sendExpiredPollReminders(DateTimeImmutable $now, callable $notifyUsers): array
    {
        $polls = $this->pollRepository->findExpiringWithoutReminder();
        $notifications = 0;

        foreach ($polls as $poll) {
            $poll->markReminderSent();
            $this->pollRepository->save($poll);
            $notifyUsers($poll);
            $notifications++;
        }

        return ['polls' => count($polls), 'notifications' => $notifications];
    }
}
