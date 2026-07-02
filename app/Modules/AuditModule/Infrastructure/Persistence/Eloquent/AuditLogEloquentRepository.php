<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Persistence\Eloquent;

use App\Modules\AuditModule\Domain\Aggregates\AuditLogEntry;
use App\Modules\AuditModule\Domain\Repositories\AuditLogRepository;
use DateTimeImmutable;

final class AuditLogEloquentRepository implements AuditLogRepository
{
    public function save(AuditLogEntry $entry): void
    {
        $model = new AuditLogModel([
            'entity_type' => $entry->entityType()->value(),
            'entity_id' => (string) $entry->entityId()->value(),
            'action' => $entry->action()->value(),
            'before' => $entry->before()?->data(),
            'after' => $entry->after()?->data(),
            'user_id' => $entry->userId()?->value(),
            'ip_address' => $entry->ipAddress()?->value(),
        ]);

        $model->save();

        $entry->setId($model->id);
    }

    public function findById(int $id): ?AuditLogEntry
    {
        $model = AuditLogModel::with('user')->find($id);

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function paginate(array $filters, int $perPage = 20, int $page = 1): array
    {
        $query = AuditLogModel::with('user')
            ->filter($filters)
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn (AuditLogModel $m) => $this->toDomain($m))
            ->all();

        return [
            'items' => $items,
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'page' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'paginator' => $paginator,
        ];
    }

    public function count(array $filters): int
    {
        return AuditLogModel::filter($filters)->count();
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff, int $chunkSize = 500): int
    {
        $deleted = 0;

        AuditLogModel::where('created_at', '<', $cutoff->format('Y-m-d H:i:s'))
            ->chunkById($chunkSize, function ($logs) use (&$deleted) {
                foreach ($logs as $log) {
                    $log->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    public function countOlderThan(DateTimeImmutable $cutoff): int
    {
        return AuditLogModel::where('created_at', '<', $cutoff->format('Y-m-d H:i:s'))->count();
    }

    public function allMatching(array $filters): array
    {
        return AuditLogModel::with('user')
            ->filter($filters)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AuditLogModel $m) => $this->toDomain($m))
            ->all();
    }

    private function toDomain(AuditLogModel $model): AuditLogEntry
    {
        $entry = AuditLogEntry::record(
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            action: $model->action,
            before: $model->before,
            after: $model->after,
            userId: $model->user_id,
            ipAddress: $model->ip_address,
        );
        $entry->setId($model->id);

        return $entry;
    }
}
