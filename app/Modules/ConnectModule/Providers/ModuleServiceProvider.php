<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Providers;

use App\Modules\ConnectModule\Console\Commands\AutoImportUccxCommand;
use App\Modules\ConnectModule\Console\Commands\CuicBackfillCommand;
use App\Modules\ConnectModule\Console\Commands\CuicRealtimeSyncCommand;
use App\Modules\ConnectModule\Console\Commands\CuicSyncCommand;
use App\Modules\ConnectModule\Console\Commands\FinesseSyncCommand;
use App\Modules\ConnectModule\Console\Commands\ImportUccxDataCommand;
use App\Modules\ConnectModule\Console\Commands\TestCuicAgentDetailCommand;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\CaseSubtype;
use App\Modules\ConnectModule\Models\Channel;
use App\Modules\ConnectModule\Policies\CallQueuePolicy;
use App\Modules\ConnectModule\Policies\CallRecordPolicy;
use App\Modules\ConnectModule\Policies\CaseSubtypePolicy;
use App\Modules\ConnectModule\Policies\ChannelPolicy;
use App\Modules\ConnectModule\Repositories\EloquentTelemetryRealtimeRepository;
use App\Modules\ConnectModule\Services\TelemetryService;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TelemetryServiceInterface::class,
            TelemetryService::class
        );

        $this->app->singleton(
            TelemetryRealtimeRepositoryInterface::class,
            EloquentTelemetryRealtimeRepository::class
        );
    }

    public function boot(): void
    {
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'connect');
        }

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportUccxDataCommand::class,
                AutoImportUccxCommand::class,
                TestCuicAgentDetailCommand::class,
                CuicSyncCommand::class,
                CuicBackfillCommand::class,
                FinesseSyncCommand::class,
                CuicRealtimeSyncCommand::class,
            ]);
        }

        Gate::policy(CallRecord::class, CallRecordPolicy::class);
        Gate::policy(CallQueue::class, CallQueuePolicy::class);
        Gate::policy(CaseSubtype::class, CaseSubtypePolicy::class);
    }

    private function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Channel::class, ChannelPolicy::class);
    }
}
