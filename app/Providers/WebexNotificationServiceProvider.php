<?php

declare(strict_types=1);

namespace App\Providers;

use App\Notifications\Channels\WebexChannel;
use App\Services\WebexService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class WebexNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebexService::class);
    }

    public function boot(): void
    {
        Notification::extend('webex', function ($app) {
            return $app->make(WebexChannel::class);
        });
    }
}
