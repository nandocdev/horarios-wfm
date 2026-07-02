<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\PollMapper;
use App\Src\Platform\Domain\Entities\Poll;
use App\Src\Platform\Domain\Repositories\PollRepositoryInterface;
use DateTimeImmutable;

final class EloquentPollRepository implements PollRepositoryInterface {
    public function save(Poll $poll): Poll {
        $eloquent = EloquentPoll::updateOrCreate(
            ['id' => $poll->id()],
            PollMapper::toEloquent($poll),
        );

        return PollMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?Poll {
        $eloquent = EloquentPoll::with(['categories', 'tags'])->find($id);

        if ($eloquent === null) {
            return null;
        }

        return PollMapper::toDomain($eloquent);
    }

    public function findAll(int $perPage = 25, array $filters = []): array {
        $query = EloquentPoll::with(['categories', 'tags']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('question', 'ilike', "%{$search}%");
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $filters['category_id']));
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentPoll $eloquent) => PollMapper::toDomain($eloquent))
            ->items();
    }

    public function findActive(int $perPage = 25): array {
        $query = EloquentPoll::with(['categories', 'tags'])
            ->where('status', 'published')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentPoll $eloquent) => PollMapper::toDomain($eloquent))
            ->items();
    }

    public function findExpired(int $perPage = 25): array {
        $query = EloquentPoll::with(['categories', 'tags'])
            ->where('status', 'published')
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentPoll $eloquent) => PollMapper::toDomain($eloquent))
            ->items();
    }

    public function findPendingReview(int $perPage = 25): array {
        $query = EloquentPoll::with(['categories', 'tags'])
            ->where('status', 'pending_review')
            ->orderBy('created_at', 'asc');

        return $query->paginate($perPage)
            ->through(fn(EloquentPoll $eloquent) => PollMapper::toDomain($eloquent))
            ->items();
    }

    public function countByStatus(string $status): int {
        return EloquentPoll::where('status', $status)->count();
    }

    public function recordVote(int $pollId, int $userId, string $answer): void {
        EloquentPollResponse::updateOrCreate(
            ['poll_id' => $pollId, 'user_id' => $userId],
            ['answer' => $answer],
        );
    }

    public function hasUserVoted(int $pollId, int $userId): bool {
        return EloquentPollResponse::where('poll_id', $pollId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getResults(int $pollId): array {
        $poll = EloquentPoll::findOrFail($pollId);
        $options = $poll->options ?? [];
        $totalVotes = EloquentPollResponse::where('poll_id', $pollId)->count();

        $votes = EloquentPollResponse::where('poll_id', $pollId)
            ->selectRaw('answer, COUNT(*) as count')
            ->groupBy('answer')
            ->pluck('count', 'answer')
            ->toArray();

        $results = [];

        foreach ($options as $option) {
            $answer = $option['value'] ?? $option;
            $count = (int) ($votes[$answer] ?? 0);
            $percentage = $totalVotes > 0 ? round(($count / $totalVotes) * 100, 1) : 0;

            $results[] = [
                'answer' => $answer,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $results;
    }

    public function delete(Poll $poll): void {
        EloquentPoll::where('id', $poll->id())->delete();
    }

    public function archiveExpired(DateTimeImmutable $now): int {
        return EloquentPoll::where('status', 'published')
            ->where('archive_at', '<=', $now->format('Y-m-d H:i:s'))
            ->update(['status' => 'archived']);
    }

    public function findExpiredWithoutReminder(DateTimeImmutable $now): array {
        return EloquentPoll::with(['categories', 'tags'])
            ->where('status', 'published')
            ->where('expires_at', '<=', $now->format('Y-m-d H:i:s'))
            ->whereNull('reminder_sent_at')
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(fn(EloquentPoll $eloquent) => PollMapper::toDomain($eloquent))
            ->toArray();
    }

    public function findVoterIds(int $pollId): array {
        return EloquentPollResponse::where('poll_id', $pollId)
            ->pluck('user_id')
            ->toArray();
    }

    public function markReminderSent(array $polls, DateTimeImmutable $now): void {
        $ids = array_map(fn(Poll $poll) => $poll->id(), $polls);
        EloquentPoll::whereIn('id', $ids)
            ->update(['reminder_sent_at' => $now->format('Y-m-d H:i:s')]);
    }
}
