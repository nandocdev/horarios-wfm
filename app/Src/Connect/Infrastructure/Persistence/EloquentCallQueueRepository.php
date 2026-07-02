<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\CallQueue;
use App\Src\Connect\Domain\Repositories\CallQueueRepositoryInterface;

final class EloquentCallQueueRepository implements CallQueueRepositoryInterface
{
    public function save(CallQueue $queue): CallQueue
    {
        $data = ConnectMapper::callQueueToEloquent($queue);

        if ($queue->id() !== null) {
            $eloquent = EloquentCallQueue::findOrFail($queue->id());
            $eloquent->update($data);
        } else {
            $eloquent = EloquentCallQueue::create($data);
        }

        return ConnectMapper::callQueueToDomain($eloquent->fresh());
    }

    public function findById(int $id): ?CallQueue
    {
        $eloquent = EloquentCallQueue::find($id);
        return $eloquent ? ConnectMapper::callQueueToDomain($eloquent) : null;
    }

    public function findByName(string $name): ?CallQueue
    {
        $eloquent = EloquentCallQueue::where('name', $name)->first();
        return $eloquent ? ConnectMapper::callQueueToDomain($eloquent) : null;
    }

    public function findAll(): array
    {
        return EloquentCallQueue::all()
            ->map(fn (EloquentCallQueue $e) => ConnectMapper::callQueueToDomain($e))
            ->toArray();
    }

    public function findAllActive(): array
    {
        return EloquentCallQueue::where('is_active', true)
            ->get()
            ->map(fn (EloquentCallQueue $e) => ConnectMapper::callQueueToDomain($e))
            ->toArray();
    }

    public function delete(int $id): void
    {
        EloquentCallQueue::destroy($id);
    }
}
