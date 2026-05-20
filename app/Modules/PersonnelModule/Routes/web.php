<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Http\Controllers\EmployeeController;
use App\Modules\PersonnelModule\Http\Controllers\EmployeeExportController;
use App\Modules\PersonnelModule\Http\Controllers\LocationController;
use App\Modules\PersonnelModule\Livewire\CreateDepartment;
use App\Modules\PersonnelModule\Livewire\CreateDirectorate;
use App\Modules\PersonnelModule\Livewire\CreatePosition;
use App\Modules\PersonnelModule\Livewire\CreateTeam;
use App\Modules\PersonnelModule\Livewire\EditDepartment;
use App\Modules\PersonnelModule\Livewire\EditDirectorate;
use App\Modules\PersonnelModule\Livewire\EditPosition;
use App\Modules\PersonnelModule\Livewire\EditTeam;
use App\Modules\PersonnelModule\Livewire\ListDepartments;
use App\Modules\PersonnelModule\Livewire\ListDirectorates;
use App\Modules\PersonnelModule\Livewire\ListPositions;
use App\Modules\PersonnelModule\Livewire\ListTeams;
use App\Modules\PersonnelModule\Livewire\ManageTeamAssignments;
use App\Modules\PersonnelModule\Livewire\ManageTeamMembers;
use App\Modules\PersonnelModule\Livewire\ShowDepartment;
use App\Modules\PersonnelModule\Livewire\ShowDirectorate;
use App\Modules\PersonnelModule\Livewire\ShowPosition;
use App\Modules\PersonnelModule\Livewire\ShowTeam;
use App\Modules\PersonnelModule\Livewire\StaffingSummary;
use App\Modules\PersonnelModule\Livewire\TeamMemberTransfer;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Personnel Module Routes
|--------------------------------------------------------------------------
*/

// --- Employees Domain ---
Route::group(['prefix' => 'employees', 'as' => 'employees.'], function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/import', [EmployeeController::class, 'import'])
        ->name('import')
        ->can('import', Employee::class);
    Route::get('/export', EmployeeExportController::class)
        ->name('export')
        ->can('export', Employee::class);
    Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');

    // Team Management (within employees prefix historically)
    Route::get('/teams/manage', ManageTeamAssignments::class)->name('teams.manage');
});

// --- Organization Domain ---
Route::group(['prefix' => 'organization', 'as' => 'organization.'], function () {
    // Directorates
    Route::get('directorates', ListDirectorates::class)->name('directorates.index');
    Route::get('directorates/create', CreateDirectorate::class)->name('directorates.create');
    Route::get('directorates/{directorate}', ShowDirectorate::class)->name('directorates.show');
    Route::get('directorates/{directorate}/edit', EditDirectorate::class)->name('directorates.edit');

    // Departments
    Route::get('departments', ListDepartments::class)->name('departments.index');
    Route::get('departments/create', CreateDepartment::class)->name('departments.create');
    Route::get('departments/{department}', ShowDepartment::class)->name('departments.show');
    Route::get('departments/{department}/edit', EditDepartment::class)->name('departments.edit');

    // Positions
    Route::get('positions', ListPositions::class)->name('positions.index');
    Route::get('positions/create', CreatePosition::class)->name('positions.create');
    Route::get('positions/{position}', ShowPosition::class)->name('positions.show');
    Route::get('positions/{position}/edit', EditPosition::class)->name('positions.edit');

    // Teams
    Route::get('teams', ListTeams::class)->name('teams.index');
    Route::get('teams/create', CreateTeam::class)->name('teams.create');
    Route::get('teams/{team}', ShowTeam::class)->name('teams.show');
    Route::get('teams/{team}/edit', EditTeam::class)->name('teams.edit');
    Route::get('teams/{team}/members', ManageTeamMembers::class)->name('teams.members');
    Route::get('teams/{team}/transfer', TeamMemberTransfer::class)->name('teams.transfer');
});

// --- Location Domain ---
Route::group(['prefix' => 'location', 'as' => 'location.'], function () {
    Route::get('/', [LocationController::class, 'index'])->name('index');
    Route::get('/provinces', [LocationController::class, 'provinces'])->name('provinces');
    Route::get('/districts/{province}', [LocationController::class, 'districts'])->name('districts');
    Route::get('/townships/{district}', [LocationController::class, 'townships'])->name('townships');
});

// --- Personnel (New Consolidated Prefix) ---
Route::group(['prefix' => 'personnel', 'as' => 'personnel.'], function () {
    Route::get('/', function () {
        return redirect()->route('employees.index');
    })->name('index');

    Route::get('/reports/staffing', StaffingSummary::class)
        ->name('staffing-summary')
        ->can('reports.staffing');
});
