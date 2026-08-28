<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Providers;

use App\Modules\AnalyticsModule\Console\Commands\CalculateDailyKpisCommand;
use App\Shared\Providers\AbstractModuleServiceProvider;

class ModuleServiceProvider extends AbstractModuleServiceProvider
{
    protected ?string $viewNamespace = 'analytics';

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                CalculateDailyKpisCommand::class,
            ]);
        }
    }
}
