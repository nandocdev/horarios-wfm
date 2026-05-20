<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Providers;

use App\Modules\OperationsModule\Actions\GetStandardizedPerformanceAction;
use App\Modules\OperationsModule\Console\Commands\ReconcileAttendanceCommand;
use App\Modules\OperationsModule\Livewire\AgentRealtimeCard;
use App\Modules\OperationsModule\Livewire\AgentTimeline;
use App\Modules\OperationsModule\Livewire\Dashboard;
use App\Modules\OperationsModule\Livewire\PerformanceScorecard;
use App\Modules\OperationsModule\Livewire\RealtimeMonitoring;
use App\Modules\OperationsModule\Livewire\TeamPerformanceSummary;
use App\Modules\OperationsModule\Services\PerformanceService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            GetStandardizedPerformanceAction::class
        );

        $this->app->singleton(
            PerformanceService::class
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ReconcileAttendanceCommand::class,
            ]);
        }

        // Registro de rutas, vistas, etc.
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'operations');
        }

        Livewire::component('operations.realtime-monitoring', RealtimeMonitoring::class);
        Livewire::component('operations.performance-scorecard', PerformanceScorecard::class);
        Livewire::component('operations.team-performance-summary', TeamPerformanceSummary::class);
        Livewire::component('operations.agent-realtime-card', AgentRealtimeCard::class);
        Livewire::component('operations.agent-timeline', AgentTimeline::class);
        Livewire::component('operations.dashboard', Dashboard::class);
    }
}
