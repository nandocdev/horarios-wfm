<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Policies\EmployeePolicy;
use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Directorate;
use App\Modules\PersonnelModule\Models\Position;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Str;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['code' => 'ADM', 'hierarchy_level' => 99]);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web'], ['code' => 'SUP', 'hierarchy_level' => 2]);

    foreach ([
        'employees.view',
        'employees.view.others',
        'employees.view.all',
        'employees.edit',
        'employees.edit.others',
        'employees.edit.all',
        'employees.delete',
        'employees.delete.others',
        'employees.delete.all',
        'employees.force_delete',
        'employees.force_delete.others',
        'employees.force_delete.all',
        'employees.export',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

function createEmployeeWithUser(mixed $teamId = null): array
{
    $team = $teamId ? Team::query()->findOrFail($teamId) : Team::factory()->create();
    $suffix = str_replace('.', '', uniqid('', true));
    $directorate = Directorate::firstOrCreate(
        ['name' => 'DIR-POL-'.$suffix],
        ['description' => 'Policy test']
    );
    $department = Department::firstOrCreate(
        ['name' => 'DEP-POL-'.$suffix, 'directorate_id' => $directorate->id],
        ['description' => 'Policy test']
    );
    $positionCode = Str::upper(Str::random(8));
    $position = Position::firstOrCreate(
        ['name' => 'POS-POL-'.$suffix, 'department_id' => $department->id],
        ['description' => 'Policy test', 'is_active' => true, 'position_code' => $positionCode]
    );
    $status = EmploymentStatus::firstOrCreate(
        ['code' => 'active-pol-'.substr($suffix, 0, 8)],
        ['name' => 'Active Policy', 'is_active' => true]
    );

    $user = User::factory()->create(['force_password_change' => false]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
    ]);

    return [$user, $employee, $team];
}

it('applies own vs others scope in view and update', function () {
    [$authUser, $authEmployee, $team] = createEmployeeWithUser();
    [, $sameTeamEmployee] = createEmployeeWithUser($team->id);
    [, $otherTeamEmployee] = createEmployeeWithUser();

    $authUser->givePermissionTo(['employees.view', 'employees.edit']);

    $policy = new EmployeePolicy;

    expect($policy->view($authUser, $authEmployee))->toBeTrue()
        ->and($policy->update($authUser, $authEmployee))->toBeTrue()
        ->and($policy->view($authUser, $sameTeamEmployee))->toBeFalse()
        ->and($policy->update($authUser, $sameTeamEmployee))->toBeFalse();

    $authUser->givePermissionTo(['employees.view.others', 'employees.edit.others']);

    expect($policy->view($authUser, $sameTeamEmployee))->toBeTrue()
        ->and($policy->update($authUser, $sameTeamEmployee))->toBeTrue()
        ->and($policy->view($authUser, $otherTeamEmployee))->toBeFalse();
});

it('allows force delete only with high privilege permission', function () {
    [$authUser, $authEmployee, $team] = createEmployeeWithUser();
    [, $sameTeamEmployee] = createEmployeeWithUser($team->id);

    $policy = new EmployeePolicy;

    expect($policy->forceDelete($authUser, $authEmployee))->toBeFalse()
        ->and($policy->forceDelete($authUser, $sameTeamEmployee))->toBeFalse();

    $authUser->givePermissionTo('employees.force_delete.all');

    expect($policy->forceDelete($authUser, $authEmployee))->toBeTrue()
        ->and($policy->forceDelete($authUser, $sameTeamEmployee))->toBeTrue();
});

it('returns effective permissions with admin override and hierarchy metadata', function () {
    [$adminUser, $adminEmployee] = createEmployeeWithUser();
    $adminUser->assignRole('admin');

    $policy = new EmployeePolicy;
    $effective = $policy->effectivePermissions($adminUser, $adminEmployee);

    expect($effective['admin_override'])->toBeTrue()
        ->and($effective['scope'])->toBe('all')
        ->and($effective['hierarchy_level'])->toBe(99)
        ->and($effective['can_force_delete'])->toBeTrue()
        ->and($effective['can_export'])->toBeTrue();
});
