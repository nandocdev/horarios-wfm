<?php

declare(strict_types=1);

use App\Modules\OperationsModule\Livewire\AdvancedProductivityDashboard;
use App\Modules\OperationsModule\Livewire\AgentPerformanceDashboard;
use App\Modules\OperationsModule\Livewire\ControlTower\ControlTowerDashboard;
use App\Modules\OperationsModule\Livewire\DailyReport;
use App\Modules\OperationsModule\Livewire\IntradayAvailability;
use App\Modules\OperationsModule\Livewire\PerformanceScorecard;
use App\Modules\OperationsModule\Livewire\QueuePerformanceReport;
use App\Modules\OperationsModule\Livewire\RealtimeMonitoring;
use App\Modules\OperationsModule\Livewire\ReportingFrameworkIndex;
use App\Modules\OperationsModule\Livewire\TeamPerformanceSummary;
use App\Modules\PersonnelModule\Models\Employee;
use App\Reports\EmployeePerformanceReport;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/operations/dashboard', ControlTowerDashboard::class)->name('operations.dashboard');
    Route::get('/operations/control-tower', ControlTowerDashboard::class)->name('operations.control-tower');
    Route::get('/operations/realtime', RealtimeMonitoring::class)->name('operations.realtime');
    Route::get('/operations/reporte-diario', DailyReport::class)->name('operations.daily-report');
    Route::get('/operations/availability', IntradayAvailability::class)->name('operations.availability');
    Route::get('/operations/performance', PerformanceScorecard::class)->name('operations.performance');
    Route::get('/operations/agent-performance/{employee?}', AgentPerformanceDashboard::class)->name('operations.agent-performance');
    Route::get('/operations/team-performance', TeamPerformanceSummary::class)->name('operations.team-performance');
    Route::get('/operations/advanced-analytics', AdvancedProductivityDashboard::class)->name('operations.advanced-analytics');
    Route::get('/operations/queue-performance', QueuePerformanceReport::class)->name('operations.queue-performance');
    Route::get('/operations/reports', ReportingFrameworkIndex::class)->name('operations.reports');

    Route::get('/operations/reports/performance/{employee}/pdf', function (int $employee) {
        $emp = Employee::findOrFail($employee);
        $report = new EmployeePerformanceReport($emp);

        return $report->stream();
    })->name('operations.reports.performance-pdf');
});
