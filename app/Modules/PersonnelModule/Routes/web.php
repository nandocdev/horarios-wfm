<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Http\Controllers\EmployeeController;
use App\Modules\PersonnelModule\Http\Controllers\EmployeeExportController;
use App\Modules\PersonnelModule\Livewire\CreateTeam;
use App\Modules\PersonnelModule\Livewire\EditTeam;
use App\Modules\PersonnelModule\Livewire\ListTeams;
use App\Modules\PersonnelModule\Livewire\ManageTeamAssignments;
use App\Modules\PersonnelModule\Livewire\ManageTeamMembers;
use App\Modules\PersonnelModule\Livewire\ShowTeam;
use App\Modules\PersonnelModule\Livewire\StaffingSummary;
use App\Modules\PersonnelModule\Livewire\TeamMemberTransfer;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Facades\Route;

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

    Route::get('/teams/manage', ManageTeamAssignments::class)
        ->name('teams.manage')
        ->can('manageTeamAssignments', Employee::class);
});

Route::group(['prefix' => 'organization', 'as' => 'organization.'], function () {
    Route::get('teams', ListTeams::class)->name('teams.index');
    Route::get('teams/create', CreateTeam::class)->name('teams.create');
    Route::get('teams/{team}', ShowTeam::class)->name('teams.show');
    Route::get('teams/{team}/edit', EditTeam::class)->name('teams.edit');
    Route::get('teams/{team}/members', ManageTeamMembers::class)->name('teams.members');
    Route::get('teams/{team}/transfer', TeamMemberTransfer::class)->name('teams.transfer');
});

Route::group(['prefix' => 'personnel', 'as' => 'personnel.'], function () {
    Route::get('/', function () {
        return redirect()->route('employees.index');
    })->name('index');

    Route::get('/reports/staffing', StaffingSummary::class)
        ->name('staffing-summary')
        ->can('viewAny', Employee::class);
});
