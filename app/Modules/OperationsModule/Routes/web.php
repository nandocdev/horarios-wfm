<?php

declare(strict_types=1);

use App\Modules\OperationsModule\Livewire\AdvancedProductivityDashboard;
use App\Modules\OperationsModule\Livewire\Dashboard;
use App\Modules\OperationsModule\Livewire\IntradayAvailability;
use App\Modules\OperationsModule\Livewire\PerformanceScorecard;
use App\Modules\OperationsModule\Livewire\QueuePerformanceReport;
use App\Modules\OperationsModule\Livewire\RealtimeMonitoring;
use App\Modules\OperationsModule\Livewire\ReportingFrameworkIndex;
use App\Modules\OperationsModule\Livewire\TeamPerformanceSummary;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/operations/dashboard', Dashboard::class)->name('operations.dashboard');
    Route::get('/operations/realtime', RealtimeMonitoring::class)->name('operations.realtime');
    Route::get('/operations/availability', IntradayAvailability::class)->name('operations.availability');
    Route::get('/operations/performance', PerformanceScorecard::class)->name('operations.performance');
    Route::get('/operations/team-performance', TeamPerformanceSummary::class)->name('operations.team-performance');
    Route::get('/operations/advanced-analytics', AdvancedProductivityDashboard::class)->name('operations.advanced-analytics');
    Route::get('/operations/queue-performance', QueuePerformanceReport::class)->name('operations.queue-performance');
    Route::get('/operations/reports', ReportingFrameworkIndex::class)->name('operations.reports');
});
