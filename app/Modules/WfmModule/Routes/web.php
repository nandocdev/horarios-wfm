<?php

declare(strict_types=1);

use App\Modules\WfmModule\Livewire\EmployeeWeeklyPlanning;
use App\Modules\WfmModule\Livewire\LeaveRequestHistory;
use App\Modules\WfmModule\Livewire\ManageAbsenceReasons;
use App\Modules\WfmModule\Livewire\ManageActivityTypes;
use App\Modules\WfmModule\Livewire\ManageAgentStates;
use App\Modules\WfmModule\Livewire\ManageIntradayActivities;
use App\Modules\WfmModule\Livewire\ManagerApprovals;
use App\Modules\WfmModule\Livewire\ManageScheduledActivities;
use App\Modules\WfmModule\Livewire\ManageScheduleExceptions;
use App\Modules\WfmModule\Livewire\ManageSchedules;
use App\Modules\WfmModule\Livewire\MyDay;
use App\Modules\WfmModule\Livewire\MyMetrics;
use App\Modules\WfmModule\Livewire\MySchedule;
use App\Modules\WfmModule\Livewire\MyTeam;
use App\Modules\WfmModule\Livewire\OperationalSettings;

use App\Modules\WfmModule\Livewire\RequestLeave;
use App\Modules\WfmModule\Livewire\RequestShiftSwap;
use App\Modules\WfmModule\Livewire\SwapRequestHistory;
use App\Modules\WfmModule\Livewire\TeamWeeklyPlanning;
use App\Modules\WfmModule\Livewire\WeeklyPlanning;
use App\Modules\WfmModule\Livewire\WeeklyPlanningTeams;
use App\Modules\WfmModule\Livewire\WfmSwapApprovals;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('schedules')->name('schedules.')->group(function () {
    // Autogestión (Operador)
    Route::get('/my-schedule', MySchedule::class)->name('my-schedule');
    Route::get('/my-day', MyDay::class)->name('my-day');
    Route::get('/my-metrics', MyMetrics::class)->name('my-metrics');
    Route::get('/my-team', MyTeam::class)->name('my-team');
    Route::get('/swap-request', RequestShiftSwap::class)->name('swap-request');
    Route::get('/swap-history', SwapRequestHistory::class)->name('swap-history');
    Route::get('/leave-request/{type?}', RequestLeave::class)->name('leave-request');
    Route::get('/leave-history', LeaveRequestHistory::class)->name('leave-history');

    // Operación (Coordinador/WFM)

    Route::get('/wfm-approvals', WfmSwapApprovals::class)->name('wfm-approvals');
    Route::get('/manager-approvals', ManagerApprovals::class)->name('manager-approvals');
    Route::get('/planning', WeeklyPlanning::class)->name('planning');
    Route::get('/intraday-activities/manage', ManageIntradayActivities::class)->name('intraday-activities.manage');
    Route::get('/exceptions', ManageScheduleExceptions::class)->name('exceptions');
    Route::get('/planning/{week}/teams', WeeklyPlanningTeams::class)->name('planning.teams');
    Route::get('/planning/{week}/team/{team}', TeamWeeklyPlanning::class)->name('planning.team');
    Route::get('/planning/{week}/employee/{employee}', EmployeeWeeklyPlanning::class)->name('planning.employee');

    // Configuración (Catálogos)
    Route::get('/shifts', ManageSchedules::class)->name('shifts');
    Route::get('/activity-types', ManageActivityTypes::class)->name('activity-types');
    Route::get('/absence-reasons', ManageAbsenceReasons::class)->name('absence-reasons');
    Route::get('/agent-states', ManageAgentStates::class)->name('agent-states');
    Route::get('/scheduled-activities', ManageScheduledActivities::class)->name('scheduled-activities');
    Route::get('/operational-settings', OperationalSettings::class)->name('operational-settings');

    Route::get('/reports/requests', \App\Modules\WfmModule\Livewire\RequestSummary::class)
        ->name('request-summary')
        ->can('reports.requests');
});
