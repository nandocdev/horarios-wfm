<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \App\Modules\OperationsModule\Actions\GetStandardizedPerformanceAction::class
        );

        $this->app->singleton(
            \App\Modules\OperationsModule\Services\PerformanceService::class
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\OperationsModule\Console\Commands\ReconcileAttendanceCommand::class,
            ]);
        }

        // Registro de rutas, vistas, etc.
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'operations');
        }

        \Livewire\Livewire::component('operations.realtime-monitoring', \App\Modules\OperationsModule\Livewire\RealtimeMonitoring::class);
        \Livewire\Livewire::component('operations.performance-scorecard', \App\Modules\OperationsModule\Livewire\PerformanceScorecard::class);
        \Livewire\Livewire::component('operations.team-performance-summary', \App\Modules\OperationsModule\Livewire\TeamPerformanceSummary::class);
        \Livewire\Livewire::component('operations.agent-realtime-card', \App\Modules\OperationsModule\Livewire\AgentRealtimeCard::class);
        \Livewire\Livewire::component('operations.agent-timeline', \App\Modules\OperationsModule\Livewire\AgentTimeline::class);
        \Livewire\Livewire::component('operations.dashboard', \App\Modules\OperationsModule\Livewire\Dashboard::class);
    }
}
