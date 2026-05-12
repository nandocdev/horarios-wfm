<?php

declare(strict_types=1);

use App\Modules\EmployeesModule\Livewire\ListEmployees;
use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\EmployeesModule\Models\EmploymentStatus;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\OrganizationModule\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('keeps query growth bounded with eager loading in server-side pagination', function () {
    $team = Team::factory()->create();
    $suffix = str_replace('.', '', uniqid('', true));
    $directorate = Directorate::firstOrCreate(
        ['name' => 'DIR-N1-'.$suffix],
        ['description' => 'N+1 Test']
    );
    $department = Department::firstOrCreate(
        ['name' => 'DEP-N1-'.$suffix, 'directorate_id' => $directorate->id],
        ['description' => 'N+1 Test']
    );
    $position = Position::firstOrCreate(
        ['name' => 'POS-N1-'.$suffix, 'department_id' => $department->id],
        ['description' => 'N+1 Test', 'is_active' => true, 'position_code' => Str::upper(Str::random(8))]
    );
    $status = EmploymentStatus::firstOrCreate(
        ['code' => 'active-n1-'.substr($suffix, 0, 8)],
        ['name' => 'Active N1', 'is_active' => true]
    );

    Employee::factory()->count(2)->create([
        'team_id' => $team->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(ListEmployees::class)
        ->set('perPage', 15)
        ->assertOk();

    $queriesWith2 = count(DB::getQueryLog());

    Employee::factory()->count(13)->create([
        'team_id' => $team->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(ListEmployees::class)
        ->set('perPage', 15)
        ->assertOk();

    $queriesWith15 = count(DB::getQueryLog());

    expect($queriesWith15 - $queriesWith2)->toBeLessThan(4);
});
