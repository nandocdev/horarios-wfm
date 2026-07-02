<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Repositories\CallRecordRepositoryInterface;

final class EloquentCallRecordRepository implements CallRecordRepositoryInterface
{
    public function save(CallRecord $record): CallRecord
    {
        $data = ConnectMapper::callRecordToEloquent($record);

        if ($record->id() !== null) {
            $eloquent = EloquentCallRecord::findOrFail($record->id());
            $eloquent->update($data);
        } else {
            $eloquent = EloquentCallRecord::create($data);
        }

        return ConnectMapper::callRecordToDomain($eloquent->fresh());
    }

    public function findById(int $id): ?CallRecord
    {
        $eloquent = EloquentCallRecord::find($id);
        return $eloquent ? ConnectMapper::callRecordToDomain($eloquent) : null;
    }

    public function findByCiscoCallId(string $ciscoCallId): ?CallRecord
    {
        $eloquent = EloquentCallRecord::where('cisco_call_id', $ciscoCallId)->first();
        return $eloquent ? ConnectMapper::callRecordToDomain($eloquent) : null;
    }

    public function findOpenByEmployee(int $employeeId): array
    {
        return EloquentCallRecord::where('employee_id', $employeeId)
            ->whereNull('closed_at')
            ->get()
            ->map(fn (EloquentCallRecord $e) => ConnectMapper::callRecordToDomain($e))
            ->toArray();
    }

    public function findOpenByQueue(int $queueId): array
    {
        return EloquentCallRecord::where('queue_id', $queueId)
            ->whereNull('closed_at')
            ->get()
            ->map(fn (EloquentCallRecord $e) => ConnectMapper::callRecordToDomain($e))
            ->toArray();
    }

    public function findCallsByDate(string $date, ?int $queueId = null): array
    {
        $query = EloquentCallRecord::whereDate('ivr_started_at', $date);

        if ($queueId !== null) {
            $query->where('queue_id', $queueId);
        }

        return $query->get()
            ->map(fn (EloquentCallRecord $e) => ConnectMapper::callRecordToDomain($e))
            ->toArray();
    }

    public function update(int $id, array $data): CallRecord
    {
        $eloquent = EloquentCallRecord::findOrFail($id);
        $eloquent->update($data);
        return ConnectMapper::callRecordToDomain($eloquent->fresh());
    }

    public function delete(int $id): void
    {
        EloquentCallRecord::destroy($id);
    }
}
