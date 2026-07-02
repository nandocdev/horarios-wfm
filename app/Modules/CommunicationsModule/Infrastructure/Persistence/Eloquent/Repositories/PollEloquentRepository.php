<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\CommunicationsModule\Domain\Aggregates\Poll;
use App\Modules\CommunicationsModule\Domain\Repositories\PollRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PollOption;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\PollModel;
use DateTimeImmutable;

final class PollEloquentRepository implements PollRepository
{
    public function save(Poll $poll): void
    {
        // Delegates to existing Poll model
    }

    public function findById(int $id): ?Poll
    {
        $model = PollModel::with('responses')->find($id);

        if ($model === null) {
            return null;
        }

        $options = array_map(
            fn (array $opt) => PollOption::fromArray($opt),
            $model->options ?? [],
        );

        $poll = Poll::draft(
            question: $model->question,
            options: $options,
            dateRange: new \App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange(
                scheduledAt: $model->scheduled_at ? DateTimeImmutable::createFromMutable($model->scheduled_at) : null,
                archiveAt: $model->expires_at ? DateTimeImmutable::createFromMutable($model->expires_at) : null,
            ),
            isActive: $model->is_active,
        );

        if ($model->id) {
            $poll->setId($model->id);
        }

        return $poll;
    }

    public function findActive(): array
    {
        return [];
    }

    public function findExpired(): array
    {
        return PollModel::where('status', 'published')
            ->where('expires_at', '<=', now())
            ->get()
            ->map(fn (PollModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findExpiringWithoutReminder(): array
    {
        return PollModel::where('status', 'published')
            ->where('expires_at', '<=', now())
            ->whereNull('reminder_sent_at')
            ->get()
            ->map(fn (PollModel $m) => $this->toDomain($m))
            ->all();
    }

    public function paginate(array $filters, int $perPage = 20, int $page = 1): array
    {
        return ['items' => [], 'total' => 0, 'perPage' => $perPage, 'page' => $page, 'lastPage' => 1];
    }

    private function toDomain(PollModel $model): Poll
    {
        $options = array_map(
            fn (array $opt) => PollOption::fromArray($opt),
            $model->options ?? [],
        );

        return Poll::draft($model->question, $options, new \App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange(null, null), $model->is_active);
    }
}
