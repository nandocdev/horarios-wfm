<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Listeners\SendShiftSwapReceivedNotification;

it('can instantiate shift swap notification listener without errors', function () {
    $listener = new SendShiftSwapReceivedNotification;
    expect($listener)->toBeInstanceOf(SendShiftSwapReceivedNotification::class);
});
