<?php

declare(strict_types=1);

use App\Modules\WfmModule\Jobs\CompileDailyScheduleSnapshotsJob;
use Illuminate\Support\Facades\Bus;

it('dispatches compile daily snapshots job when running artisan command', function () {
    Bus::fake();

    $this->artisan('schedules:compile-daily-snapshots')->assertExitCode(0);

    Bus::assertDispatched(CompileDailyScheduleSnapshotsJob::class);
});
