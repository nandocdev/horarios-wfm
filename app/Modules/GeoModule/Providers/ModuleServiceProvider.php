<?php

declare(strict_types=1);

namespace App\Modules\GeoModule\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'geo');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'location');
    }

    public function register(): void
    {
        //
    }
}
