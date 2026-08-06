<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Actions\SyncCsqRealtimeStatsAction;
use App\Modules\ConnectModule\Jobs\CuicRealtimeSyncJob;
use App\Shared\Events\SyncFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('runs the CSQ sync and re-dispatches itself with a delay', function () {
    Queue::fake();

    $this->mock(SyncCsqRealtimeStatsAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn(3);

    (new CuicRealtimeSyncJob)->handle(app(SyncCsqRealtimeStatsAction::class));

    Queue::assertPushed(CuicRealtimeSyncJob::class, fn (CuicRealtimeSyncJob $job) => $job->delay !== null);
});

it('keeps the realtime chain alive when the sync fails', function () {
    Queue::fake();
    Event::fake([SyncFailed::class]);

    $this->mock(SyncCsqRealtimeStatsAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('CUIC no disponible'));

    (new CuicRealtimeSyncJob)->handle(app(SyncCsqRealtimeStatsAction::class));

    Event::assertDispatched(SyncFailed::class);
    Queue::assertPushed(CuicRealtimeSyncJob::class);
});
