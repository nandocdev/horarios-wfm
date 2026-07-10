<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\WebexNotificationServiceProvider;
use Flux\FluxServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    WebexNotificationServiceProvider::class,
    FluxServiceProvider::class,
];
