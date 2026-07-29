<?php

declare(strict_types=1);

use App\Modules\OperationsModule\Livewire\AdvancedProductivityDashboard;
use App\Modules\OperationsModule\Livewire\AgentPerformanceDashboard;
use App\Modules\OperationsModule\Livewire\CallQuery;
use App\Modules\OperationsModule\Livewire\CapacityAnalysis;
use App\Modules\OperationsModule\Livewire\CapacityDashboard;
use App\Modules\OperationsModule\Livewire\ComparisonDashboard;
use App\Modules\OperationsModule\Livewire\ControlTower\ControlTowerDashboard;
use App\Modules\OperationsModule\Livewire\DailyReport;
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
use App\Modules\PersonnelModule\Models\Employee;
use App\Reports\EmployeePerformanceReport;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/operations/dashboard', ControlTowerDashboard::class)->name('operations.dashboard');
    Route::get('/operations/control-tower', ControlTowerDashboard::class)->name('operations.control-tower');
    Route::get('/operations/intervals', IntervalDashboard::class)->name('operations.intervals');
    Route::get('/operations/calls', CallQuery::class)->name('operations.calls');
    Route::get('/operations/forecast', ForecastManager::class)->name('operations.forecast');
    Route::get('/operations/staffing', StaffingDashboard::class)->name('operations.staffing');
    Route::get('/operations/capacity', CapacityDashboard::class)->name('operations.capacity');
    Route::get('/operations/shrinkage', ShrinkageDashboard::class)->name('operations.shrinkage');
    Route::get('/operations/scenarios', ScenarioComparison::class)->name('operations.scenarios');
    Route::get('/operations/kpis', KpiDashboard::class)->name('operations.kpis');
    Route::get('/operations/staffing-analysis', StaffingAnalysis::class)->name('operations.staffing-analysis');
    Route::get('/operations/capacity-analysis', CapacityAnalysis::class)->name('operations.capacity-analysis');
    Route::get('/operations/trends', TrendsDashboard::class)->name('operations.trends');
    Route::get('/operations/skills', SkillsHeatmap::class)->name('operations.skills');
    Route::get('/operations/comparison', ComparisonDashboard::class)->name('operations.comparison');
    Route::get('/operations/explorer', DataExplorer::class)->name('operations.explorer');
    Route::get('/operations/realtime', RealtimeMonitoring::class)->name('operations.realtime');
    Route::get('/operations/reporte-diario', DailyReport::class)->name('operations.daily-report');
    Route::get('/operations/availability', IntradayAvailability::class)->name('operations.availability');
    Route::get('/operations/performance', PerformanceScorecard::class)->name('operations.performance');
    Route::get('/operations/agent-performance/{employee?}', AgentPerformanceDashboard::class)->name('operations.agent-performance');
    Route::get('/operations/team-performance', TeamPerformanceSummary::class)->name('operations.team-performance');
    Route::get('/operations/advanced-analytics', AdvancedProductivityDashboard::class)->name('operations.advanced-analytics');
    Route::get('/operations/queues', QueuePerformanceReport::class)->name('operations.queues');
    Route::get('/operations/queue-performance', QueuePerformanceReport::class)->name('operations.queue-performance');
    Route::get('/operations/reports', ReportingFrameworkIndex::class)->name('operations.reports');

    Route::get('/operations/reports/performance/{employee}/pdf', function (int $employee) {
        $emp = Employee::findOrFail($employee);
        $report = new EmployeePerformanceReport($emp);

        return $report->stream();
    })->name('operations.reports.performance-pdf');
});
