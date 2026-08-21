<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Illuminate\Support\Facades\Cache;

final class IdempotencyService
{
    private const PROCESSING_WINDOW_MINUTES = 5;

    public function wasProcessed(string $key): bool
    {
        return Cache::has($key . ':processed');
    }

    public function markAsProcessed(string $key): void
    {
        Cache::put($key . ':processed', true, self::PROCESSING_WINDOW_MINUTES);
    }

    public function getDedupKey(string $eventType, string $identifier, string $payloadHash): string
    {
        return "idempotency:connect:{$eventType}:{$identifier}:{$payloadHash}";
    }

    public function processIfNew(string $eventType, string $identifier, string $payloadHash, \Closure $processing): bool
    {
        $dedupKey = $this->getDedupKey($eventType, $identifier, $payloadHash);

        if ($this->wasProcessed($dedupKey)) {
            return false;
        }

        $this->markAsProcessed($dedupKey);

        return $processing();
    }
}