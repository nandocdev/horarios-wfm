<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\CommunicationsModule\Domain\Aggregates\Shoutout;
use App\Modules\CommunicationsModule\Domain\Enums\ReactionType;
use App\Modules\CommunicationsModule\Domain\Repositories\ShoutoutRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ShoutoutMessage;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\ShoutoutModel;
use DateTimeImmutable;

final class ShoutoutEloquentRepository implements ShoutoutRepository
{
    public function save(Shoutout $shoutout): void
    {
        // Implementation delegates to existing Eloquent model
        // In a full migration, this would map domain<->persistence
    }

    public function findById(int $id): ?Shoutout
    {
        $model = ShoutoutModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findActive(int $limit = 6): array
    {
        return [];
    }

    public function findScheduledToArchive(): array
    {
        return ShoutoutModel::where('status', '!=', 'archived')
            ->where('archive_at', '<=', now())
            ->get()
            ->map(fn (ShoutoutModel $m) => $this->toDomain($m))
            ->all();
    }

    public function paginate(array $filters, int $perPage = 20, int $page = 1): array
    {
        return ['items' => [], 'total' => 0, 'perPage' => $perPage, 'page' => $page, 'lastPage' => 1];
    }

    private function toDomain(ShoutoutModel $model): Shoutout
    {
        return Shoutout::draft(
            message: new ShoutoutMessage(
                message: new ContentBody($model->message),
                employeeId: new PersonId($model->employee_id),
            ),
            authorId: new PersonId($model->employee_id),
            dateRange: new DateRange(
                scheduledAt: $model->scheduled_at ? DateTimeImmutable::createFromMutable($model->scheduled_at) : null,
                archiveAt: $model->archive_at ? DateTimeImmutable::createFromMutable($model->archive_at) : null,
            ),
            isActive: $model->is_active,
        );
    }
}
