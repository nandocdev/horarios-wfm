<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Flux\FluxServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    FluxServiceProvider::class,

];
