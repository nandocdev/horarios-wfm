<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Providers;

use App\Modules\OperationsModule\Actions\GetStandardizedPerformanceAction;
use App\Modules\OperationsModule\Console\Commands\ReconcileAttendanceCommand;
use App\Modules\OperationsModule\Livewire\AdvancedProductivityDashboard;
use App\Modules\OperationsModule\Livewire\AgentPerformanceDashboard;
use App\Modules\OperationsModule\Livewire\AgentRealtimeCard;
use App\Modules\OperationsModule\Livewire\AgentTimeline;
use App\Modules\OperationsModule\Livewire\Dashboard;
use App\Modules\OperationsModule\Livewire\IntradayAvailability;
use App\Modules\OperationsModule\Livewire\PerformanceScorecard;
use App\Modules\OperationsModule\Livewire\QueuePerformanceReport;
use App\Modules\OperationsModule\Livewire\RealtimeMonitoring;
use App\Modules\OperationsModule\Livewire\ReportingFrameworkIndex;
use App\Modules\OperationsModule\Livewire\TeamPerformanceSummary;
use App\Modules\OperationsModule\Livewire\Widgets\CriticalAlertsWidget;
use App\Modules\OperationsModule\Livewire\Widgets\HeroKpiWidget;
use App\Modules\OperationsModule\Livewire\Widgets\QueueStatsWidget;
use App\Modules\OperationsModule\Livewire\Widgets\RecentIncidentsWidget;
use App\Modules\OperationsModule\Livewire\Widgets\StateDistributionWidget;
use App\Modules\OperationsModule\Livewire\Widgets\VolumeComparisonWidget;
use App\Modules\OperationsModule\Services\AgentPerformanceService;
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

        $this->app->singleton(
            AgentPerformanceService::class
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
        Livewire::component('operations.intraday-availability', IntradayAvailability::class);
        Livewire::component('operations.queue-performance-report', QueuePerformanceReport::class);
        Livewire::component('operations.reporting-index', ReportingFrameworkIndex::class);
        Livewire::component('operations.performance-scorecard', PerformanceScorecard::class);
        Livewire::component('operations.team-performance-summary', TeamPerformanceSummary::class);
        Livewire::component('operations.agent-realtime-card', AgentRealtimeCard::class);
        Livewire::component('operations.agent-timeline', AgentTimeline::class);
        Livewire::component('operations.dashboard', Dashboard::class);
        Livewire::component('operations.advanced-productivity-dashboard', AdvancedProductivityDashboard::class);
        Livewire::component('operations.agent-performance-dashboard', AgentPerformanceDashboard::class);

        // Registro de Widgets del Dashboard (Lazy Loading)
        Livewire::component('operations.widgets.hero-kpi-widget', HeroKpiWidget::class);
        Livewire::component('operations.widgets.queue-stats-widget', QueueStatsWidget::class);
        Livewire::component('operations.widgets.state-distribution-widget', StateDistributionWidget::class);
        Livewire::component('operations.widgets.volume-comparison-widget', VolumeComparisonWidget::class);
        Livewire::component('operations.widgets.critical-alerts-widget', CriticalAlertsWidget::class);
        Livewire::component('operations.widgets.recent-incidents-widget', RecentIncidentsWidget::class);
    }
}
