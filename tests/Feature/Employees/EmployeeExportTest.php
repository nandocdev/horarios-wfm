<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'employees.export', 'guard_name' => 'web']);
});

it('exports employees as csv with selected ids and date range filters', function () {
    $admin = User::factory()->create(['force_password_change' => false]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('employees.export');

    $team = Team::factory()->create();
    $directorate = Directorate::firstOrCreate(['name' => 'DIR-EXP'], ['description' => 'Test']);
    $department = Department::firstOrCreate(['name' => 'DEP-EXP', 'directorate_id' => $directorate->id], ['description' => 'Test']);
    $position = Position::firstOrCreate(
        ['name' => 'POS-EXP', 'department_id' => $department->id],
        ['description' => 'Test', 'is_active' => true, 'position_code' => 'POSEXP01']
    );
    $status = EmploymentStatus::firstOrCreate(['code' => 'active-exp'], ['name' => 'Active EXP', 'is_active' => true]);

    $selected = Employee::factory()->create([
        'team_id' => $team->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
        'hire_date' => '2026-03-10',
        'first_name' => 'Ana',
        'last_name' => 'Selected',
    ]);

    Employee::factory()->create([
        'team_id' => $team->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
        'hire_date' => '2026-01-10',
        'first_name' => 'Pedro',
        'last_name' => 'OutOfRange',
    ]);

    $response = $this->actingAs($admin)->get(route('employees.export', [
        'format' => 'csv',
        'all' => 0,
        'selected' => [$selected->id],
        'date_from' => '2026-03-01',
        'date_to' => '2026-03-31',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertSee('employee_number');
    $response->assertSee($selected->employee_number);
});

it('exports employees as excel using all filtered records', function () {
    $admin = User::factory()->create(['force_password_change' => false]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('employees.export');

    $team = Team::factory()->create();
    $directorate = Directorate::firstOrCreate(['name' => 'DIR-EXP2'], ['description' => 'Test']);
    $department = Department::firstOrCreate(['name' => 'DEP-EXP2', 'directorate_id' => $directorate->id], ['description' => 'Test']);
    $position = Position::firstOrCreate(
        ['name' => 'POS-EXP2', 'department_id' => $department->id],
        ['description' => 'Test', 'is_active' => true, 'position_code' => 'POSEXP02']
    );
    $status = EmploymentStatus::firstOrCreate(['code' => 'active-exp2'], ['name' => 'Active EXP2', 'is_active' => true]);

    Employee::factory()->create([
        'team_id' => $team->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
        'is_active' => true,
        'first_name' => 'Mario',
        'last_name' => 'Excel',
    ]);

    $response = $this->actingAs($admin)->get(route('employees.export', [
        'format' => 'excel',
        'all' => 1,
        'is_active' => 1,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
    $response->assertSee('<table>', false);
    $response->assertSee('Mario Excel');
});
