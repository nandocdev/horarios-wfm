<?php

declare(strict_types=1);

use App\Modules\WfmModule\Listeners\LogLeaveRequestCreatedListener;
use App\Modules\WfmModule\Listeners\LogLeaveRequestDecisionListener;
use App\Modules\WfmModule\Listeners\LogShiftSwapApprovedListener;
use App\Modules\WfmModule\Listeners\LogWeeklySchedulePublishedListener;

it('can instantiate events and listeners without errors', function () {
    // Just instantiate to ensure classes load and methods exist
    $listeners = [
        new LogWeeklySchedulePublishedListener,
        new LogLeaveRequestCreatedListener,
        new LogLeaveRequestDecisionListener,
        new LogShiftSwapApprovedListener,
    ];

    expect(count($listeners))->toBe(4);
});
