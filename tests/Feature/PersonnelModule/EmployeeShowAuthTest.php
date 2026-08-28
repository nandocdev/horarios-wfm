<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;

beforeEach(function () {
    $this->directorate = Directorate::create(['name' => 'Dir Test']);
    $this->department = Department::create(['directorate_id' => $this->directorate->id, 'name' => 'Dept Test']);
    $this->position = Position::create(['department_id' => $this->department->id, 'name' => 'Pos Test', 'position_code' => 'PT-001']);
    $this->status = EmploymentStatus::create(['name' => 'Activo', 'code' => 'ACT']);
    $this->teamA = Team::create(['name' => 'Team A', 'is_active' => true]);
    $this->teamB = Team::create(['name' => 'Team B', 'is_active' => true]);

    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->employeeA = Employee::create([
        'employee_number' => 'EMP001',
        'username' => 'empA',
        'first_name' => 'Juan',
        'last_name' => 'Pérez A',
        'email' => 'jpereza@example.com',
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'employment_status_id' => $this->status->id,
        'team_id' => $this->teamA->id,
        'user_id' => $this->userA->id,
        'hire_date' => '2024-01-01',
        'is_active' => true,
    ]);

    $this->employeeB = Employee::create([
        'employee_number' => 'EMP002',
        'username' => 'empB',
        'first_name' => 'María',
        'last_name' => 'García B',
        'email' => 'mgarciab@example.com',
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'employment_status_id' => $this->status->id,
        'team_id' => $this->teamB->id,
        'user_id' => $this->userB->id,
        'hire_date' => '2024-01-01',
        'is_active' => true,
    ]);
});

test('usuario con employees.view ve solo su propio perfil', function () {
    $this->userA->givePermissionTo('employees.view');

    $response = $this->actingAs($this->userA)->get(route('employees.show', $this->employeeA));
    $response->assertOk();
    $response->assertSee('Juan');
});

test('usuario con employees.view NO ve empleado de otro team (403)', function () {
    $this->userA->givePermissionTo('employees.view');

    $response = $this->actingAs($this->userA)->get(route('employees.show', $this->employeeB));
    $response->assertForbidden();
});

test('usuario con employees.view.others ve empleado de su mismo team', function () {
    // Ambos en Team A
    $this->employeeB->update(['team_id' => $this->teamA->id]);

    $this->userA->givePermissionTo('employees.view.others');

    $response = $this->actingAs($this->userA)->get(route('employees.show', $this->employeeB));
    $response->assertOk();
});

test('usuario con employees.view.all ve cualquier empleado', function () {
    $this->userA->givePermissionTo('employees.view.all');

    $response = $this->actingAs($this->userA)->get(route('employees.show', $this->employeeB));
    $response->assertOk();
});

test('admin ve cualquier empleado', function () {
    $response = $this->actingAs($this->admin)->get(route('employees.show', $this->employeeB));
    $response->assertOk();
});
