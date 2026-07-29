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
        return Cache::remember('call_queues:active', self::TTL, fn () => CallQueue::active()->orderBy('name')->get()
        );
    }

    public function names(): array
    {
        return Cache::remember('call_queues:names', self::TTL, fn () => CallQueue::active()->orderBy('name')->pluck('name')->toArray()
        );
    }

    public function all(): Collection
    {
        return Cache::remember('call_queues:all', self::TTL, fn () => CallQueue::orderBy('name')->get()
        );
    }

    public function ahtGoals(): Collection
    {
        return Cache::remember('call_queues:aht_goals', self::TTL, fn () => CallQueue::pluck('aht_goal', 'name')
        );
    }

    public function selectIds(): Collection
    {
        return Cache::remember('call_queues:select_ids', self::TTL, fn () => CallQueue::active()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function refresh(): void
    {
        Cache::forget('call_queues:active');
        Cache::forget('call_queues:names');
        Cache::forget('call_queues:all');
        Cache::forget('call_queues:aht_goals');
        Cache::forget('call_queues:select_ids');
    }
}
