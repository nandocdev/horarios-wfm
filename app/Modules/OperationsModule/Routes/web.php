<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\OperationsModule\Livewire\RealtimeMonitoring;
use App\Modules\OperationsModule\Livewire\PerformanceScorecard;
use App\Modules\OperationsModule\Livewire\TeamPerformanceSummary;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/operations/realtime', RealtimeMonitoring::class)->name('operations.realtime');
    Route::get('/operations/performance', PerformanceScorecard::class)->name('operations.performance');
    Route::get('/operations/team-performance', TeamPerformanceSummary::class)->name('operations.team-performance');
});
