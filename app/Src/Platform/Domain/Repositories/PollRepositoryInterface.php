<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\Poll;
use DateTimeImmutable;

interface PollRepositoryInterface {
    public function save(Poll $poll): Poll;

    public function findById(int $id): ?Poll;

    public function findAll(int $perPage = 25, array $filters = []): array;

    public function findActive(int $perPage = 25): array;

    public function findExpired(int $perPage = 25): array;

    public function findPendingReview(int $perPage = 25): array;

    public function countByStatus(string $status): int;

    public function recordVote(int $pollId, int $userId, string $answer): void;

    public function hasUserVoted(int $pollId, int $userId): bool;

    public function getResults(int $pollId): array;

    public function delete(Poll $poll): void;

    public function archiveExpired(DateTimeImmutable $now): int;

    public function findExpiredWithoutReminder(DateTimeImmutable $now): array;

    public function findVoterIds(int $pollId): array;

    public function markReminderSent(array $polls, DateTimeImmutable $now): void;
}
