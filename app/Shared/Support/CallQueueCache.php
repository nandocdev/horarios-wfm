<?php

declare(strict_types=1);

namespace App\Shared\Support;

use App\Modules\ConnectModule\Models\CallQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CallQueueCache
{
    private const TTL = 300;

    public function active(): Collection
    {
        return once(fn () => CallQueue::active()->orderBy('name')->get());
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return Cache::remember('call_queues:names', self::TTL, fn () => CallQueue::active()->orderBy('name')->pluck('name')->toArray()
        );
    }

    public function all(): Collection
    {
        return once(fn () => CallQueue::orderBy('name')->get());
    }

    /**
     * @return array<string, int|null>
     */
    public function ahtGoals(): array
    {
        return Cache::remember('call_queues:aht_goals', self::TTL, fn () => CallQueue::pluck('aht_goal', 'name')->toArray()
        );
    }

    public function selectIds(): Collection
    {
        return once(fn () => CallQueue::active()->orderBy('name')->get(['id', 'name']));
    }

    public function refresh(): void
    {
        Cache::forget('call_queues:names');
        Cache::forget('call_queues:aht_goals');
    }
}
