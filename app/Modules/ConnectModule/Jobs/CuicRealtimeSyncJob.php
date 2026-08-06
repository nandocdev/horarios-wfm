<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Jobs;

use App\Modules\ConnectModule\Actions\SyncCsqRealtimeStatsAction;
use App\Shared\Events\SyncFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class CuicRealtimeSyncJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct()
    {
        $this->onConnection('redis');
        $this->onQueue('realtime-sync');
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping('cuic_realtime_sync')];
    }

    public function handle(SyncCsqRealtimeStatsAction $action): void
    {
        try {
            $action->execute();
        } catch (\Exception $e) {
            Log::error('CuicRealtimeSync Failure: '.$e->getMessage());
            event(new SyncFailed('cuic:sync-realtime', $e->getMessage(), 1));
        }

        self::dispatch()->delay(now()->addSeconds(15));
    }
}
