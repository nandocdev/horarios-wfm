<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Providers;

use App\Modules\OperationsModule\Actions\GetStandardizedPerformanceAction;
use App\Modules\OperationsModule\Alerts\Models\AlertEvent;
use App\Modules\OperationsModule\Alerts\Observers\AlertEventObserver;
use App\Modules\OperationsModule\Console\Commands\AggregateAgentDailyMetricsCommand;
use App\Modules\OperationsModule\Console\Commands\BackfillIntervalMetricsCommand;
use App\Modules\OperationsModule\Console\Commands\CalculateDailyMetricsCommand;
use App\Modules\OperationsModule\Console\Commands\EvaluateAlertsCommand;
use App\Modules\OperationsModule\Console\Commands\ReconcileAttendanceCommand;
use App\Modules\OperationsModule\Console\Commands\SeedAlertRules;
use App\Modules\OperationsModule\Listeners\SendAdherenceAlertNotification;
use App\Modules\OperationsModule\Listeners\SendAttendanceIncidentNotification;
use App\Modules\OperationsModule\Livewire\AdvancedProductivityDashboard;
use App\Modules\OperationsModule\Livewire\AgentPerformanceDashboard;
use App\Modules\OperationsModule\Livewire\AgentRealtimeCard;
use App\Modules\OperationsModule\Livewire\AgentTimeline;
use App\Modules\OperationsModule\Livewire\CallQuery;
use App\Modules\OperationsModule\Livewire\CapacityAnalysis;
use App\Modules\OperationsModule\Livewire\CapacityDashboard;
use App\Modules\OperationsModule\Livewire\ComparisonDashboard;
use App\Modules\OperationsModule\Livewire\ControlTower\ActivityFeedWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\AdherenceHeatmapWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\AlertFeedWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\ControlTowerDashboard;
use App\Modules\OperationsModule\Livewire\ControlTower\CoverageMatrixWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\ForecastComparisonWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\HeaderWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\HeroStatsWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\NotificationCenterWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\OccupancyChartWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\OperationalStatusWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\PendingApprovalsWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\QueueTableWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\SlaAsaChartWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\TeamPerformanceWidget;
use App\Modules\OperationsModule\Livewire\ControlTower\TimelineWidget;
use App\Modules\OperationsModule\Livewire\DailyReport;
use App\Modules\OperationsModule\Livewire\Dashboard;
use App\Modules\OperationsModule\Livewire\DataExplorer;
use App\Modules\OperationsModule\Livewire\ForecastManager;
use App\Modules\OperationsModule\Livewire\IntervalDashboard;
use App\Modules\OperationsModule\Livewire\IntradayAvailability;
use App\Modules\OperationsModule\Livewire\KpiDashboard;
use App\Modules\OperationsModule\Livewire\PerformanceScorecard;
use App\Modules\OperationsModule\Livewire\QueuePerformanceReport;
use App\Modules\OperationsModule\Livewire\RealtimeMonitoring;
use App\Modules\OperationsModule\Livewire\ReportingFrameworkIndex;
use App\Modules\OperationsModule\Livewire\ScenarioComparison;
use App\Modules\OperationsModule\Livewire\ShrinkageDashboard;
use App\Modules\OperationsModule\Livewire\SkillsHeatmap;
use App\Modules\OperationsModule\Livewire\StaffingAnalysis;
use App\Modules\OperationsModule\Livewire\StaffingDashboard;
use App\Modules\OperationsModule\Livewire\TeamPerformanceSummary;
use App\Modules\OperationsModule\Livewire\TrendsDashboard;
use App\Modules\OperationsModule\Livewire\Widgets\CriticalAlertsWidget;
use App\Modules\OperationsModule\Livewire\Widgets\HeroKpiWidget;
use App\Modules\OperationsModule\Livewire\Widgets\QueueStatsWidget;
use App\Modules\OperationsModule\Livewire\Widgets\RecentIncidentsWidget;
use App\Modules\OperationsModule\Livewire\Widgets\StateDistributionWidget;
use App\Modules\OperationsModule\Livewire\Widgets\VolumeComparisonWidget;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\OperationsModule\Models\IncidentType;
use App\Modules\OperationsModule\Policies\AgentDailyMetricPolicy;
use App\Modules\OperationsModule\Policies\AttendanceIncidentPolicy;
use App\Modules\OperationsModule\Policies\IncidentTypePolicy;
use App\Modules\OperationsModule\Repositories\EloquentAgentPerformanceRepository;
use App\Modules\OperationsModule\Services\AgentPerformanceService;
use App\Modules\OperationsModule\Services\PerformanceService;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use App\Shared\Events\AdherenceAlertTriggered;
use App\Shared\Events\AttendanceIncidentRegistered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        $this->app->singleton(
            AgentPerformanceRepositoryInterface::class,
            EloquentAgentPerformanceRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillIntervalMetricsCommand::class,
                CalculateDailyMetricsCommand::class,
                ReconcileAttendanceCommand::class,
                EvaluateAlertsCommand::class,
                SeedAlertRules::class,
                AggregateAgentDailyMetricsCommand::class,
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
        Livewire::component('operations.interval-dashboard', IntervalDashboard::class);
        Livewire::component('operations.call-query', CallQuery::class);
        Livewire::component('operations.forecast-manager', ForecastManager::class);
        Livewire::component('operations.staffing-dashboard', StaffingDashboard::class);
        Livewire::component('operations.staffing-analysis', StaffingAnalysis::class);
        Livewire::component('operations.capacity-dashboard', CapacityDashboard::class);
        Livewire::component('operations.shrinkage-dashboard', ShrinkageDashboard::class);
        Livewire::component('operations.scenario-comparison', ScenarioComparison::class);
        Livewire::component('operations.kpi-dashboard', KpiDashboard::class);
        Livewire::component('operations.capacity-analysis', CapacityAnalysis::class);
        Livewire::component('operations.trends-dashboard', TrendsDashboard::class);
        Livewire::component('operations.skills-heatmap', SkillsHeatmap::class);
        Livewire::component('operations.comparison-dashboard', ComparisonDashboard::class);
        Livewire::component('operations.data-explorer', DataExplorer::class);
        Livewire::component('operations.queue-performance-report', QueuePerformanceReport::class);
        Livewire::component('operations.reporting-index', ReportingFrameworkIndex::class);
        Livewire::component('operations.performance-scorecard', PerformanceScorecard::class);
        Livewire::component('operations.team-performance-summary', TeamPerformanceSummary::class);
        Livewire::component('operations.daily-report', DailyReport::class);
        Livewire::component('operations.agent-realtime-card', AgentRealtimeCard::class);
        Livewire::component('operations.agent-timeline', AgentTimeline::class);
        Livewire::component('operations.dashboard', Dashboard::class);
        Livewire::component('operations.control-tower', ControlTowerDashboard::class);
        Livewire::component('operations.control-tower.header-widget', HeaderWidget::class);
        Livewire::component('operations.control-tower.hero-stats-widget', HeroStatsWidget::class);
        Livewire::component('operations.control-tower.operational-status-widget', OperationalStatusWidget::class);
        Livewire::component('operations.control-tower.alert-feed-widget', AlertFeedWidget::class);
        Livewire::component('operations.control-tower.occupancy-chart-widget', OccupancyChartWidget::class);
        Livewire::component('operations.control-tower.sla-asa-chart-widget', SlaAsaChartWidget::class);
        Livewire::component('operations.control-tower.coverage-matrix-widget', CoverageMatrixWidget::class);
        Livewire::component('operations.control-tower.forecast-comparison-widget', ForecastComparisonWidget::class);
        Livewire::component('operations.control-tower.team-performance-widget', TeamPerformanceWidget::class);
        Livewire::component('operations.control-tower.pending-approvals-widget', PendingApprovalsWidget::class);
        Livewire::component('operations.control-tower.adherence-heatmap-widget', AdherenceHeatmapWidget::class);
        Livewire::component('operations.control-tower.queue-table-widget', QueueTableWidget::class);
        Livewire::component('operations.control-tower.notification-center-widget', NotificationCenterWidget::class);
        Livewire::component('operations.control-tower.activity-feed-widget', ActivityFeedWidget::class);
        Livewire::component('operations.control-tower.timeline-widget', TimelineWidget::class);
        Livewire::component('operations.advanced-productivity-dashboard', AdvancedProductivityDashboard::class);
        Livewire::component('operations.agent-performance-dashboard', AgentPerformanceDashboard::class);

        // Registro de Widgets del Dashboard (Lazy Loading)
        Livewire::component('operations.widgets.hero-kpi-widget', HeroKpiWidget::class);
        Livewire::component('operations.widgets.queue-stats-widget', QueueStatsWidget::class);
        Livewire::component('operations.widgets.state-distribution-widget', StateDistributionWidget::class);
        Livewire::component('operations.widgets.volume-comparison-widget', VolumeComparisonWidget::class);
        Livewire::component('operations.widgets.critical-alerts-widget', CriticalAlertsWidget::class);
        Livewire::component('operations.widgets.recent-incidents-widget', RecentIncidentsWidget::class);

        Gate::policy(AttendanceIncident::class, AttendanceIncidentPolicy::class);
        Gate::policy(AgentDailyMetric::class, AgentDailyMetricPolicy::class);
        Gate::policy(IncidentType::class, IncidentTypePolicy::class);

        Event::listen(
            AdherenceAlertTriggered::class,
            SendAdherenceAlertNotification::class,
        );
        Event::listen(
            AttendanceIncidentRegistered::class,
            SendAttendanceIncidentNotification::class,
        );

        // Registro de observadores de alertas
        AlertEvent::observe(AlertEventObserver::class);
    }
}
