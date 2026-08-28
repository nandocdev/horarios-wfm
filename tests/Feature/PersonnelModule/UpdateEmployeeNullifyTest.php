<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\GeoModule\Models\District;
use App\Modules\GeoModule\Models\Province;
use App\Modules\GeoModule\Models\Township;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Actions\UpdateEmployeeAction;
use App\Modules\PersonnelModule\DTOs\UpdateEmployeeDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->directorate = Directorate::create(['name' => 'Dir Test']);
    $this->department = Department::create(['directorate_id' => $this->directorate->id, 'name' => 'Dept Test']);
    $this->position = Position::create(['department_id' => $this->department->id, 'name' => 'Pos Test', 'position_code' => 'PT-001']);
    $this->status = EmploymentStatus::create(['name' => 'Activo', 'code' => 'ACT']);
    $this->team = Team::create(['name' => 'Team A', 'is_active' => true]);

    $province = Province::create(['name' => 'Panamá', 'code' => 'PA']);
    $district = District::create(['province_id' => $province->id, 'name' => 'Panamá']);
    $this->township = Township::create(['district_id' => $district->id, 'name' => 'San Francisco']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['employees.view', 'employees.edit', 'employees.edit.all']);

    $this->employee = Employee::create([
        'employee_number' => 'EMP001',
        'username' => 'emp001',
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'jperez@example.com',
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'employment_status_id' => $this->status->id,
        'team_id' => $this->team->id,
        'township_id' => $this->township->id,
        'parent_id' => null,
        'hire_date' => '2024-01-01',
        'is_active' => true,
    ]);
});

test('puede nulificar parent_id (quitar supervisor)', function () {
    $supervisor = Employee::create([
        'employee_number' => 'EMP002',
        'username' => 'sup001',
        'first_name' => 'Supervisor',
        'last_name' => 'Test',
        'email' => 'sup@example.com',
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'employment_status_id' => $this->status->id,
        'hire_date' => '2024-01-01',
        'is_active' => true,
    ]);

    $this->employee->update(['parent_id' => $supervisor->id]);
    expect($this->employee->fresh()->parent_id)->toBe($supervisor->id);

    // Nulificar parent_id
    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'parent_id' => null,
    ]));

    expect($this->employee->fresh()->parent_id)->toBeNull();
});

test('puede nulificar department_id (cambio de departamento)', function () {
    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'department_id' => null,
    ]));

    expect($this->employee->fresh()->department_id)->toBeNull();
});

test('puede nulificar position_id', function () {
    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'position_id' => null,
    ]));

    expect($this->employee->fresh()->position_id)->toBeNull();
});

test('puede nulificar township_id', function () {
    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'township_id' => $this->township->id,
    ]));

    expect($this->employee->fresh()->township_id)->toBe($this->township->id);

    // Nulificar township_id
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'township_id' => null,
    ]));

    expect($this->employee->fresh()->township_id)->toBeNull();
});

test('puede nulificar user_id', function () {
    $user = User::factory()->create();
    $this->employee->update(['user_id' => $user->id]);

    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'user_id' => null,
    ]));

    expect($this->employee->fresh()->user_id)->toBeNull();
});

test('campos no provistos NO se modifican (preservación)', function () {
    $originalDept = $this->employee->department_id;
    $originalPos = $this->employee->position_id;
    $originalTeam = $this->employee->team_id;
    $originalParent = $this->employee->parent_id;

    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'first_name' => 'Juan Actualizado',
    ]));

    $fresh = $this->employee->fresh();
    expect($fresh->first_name)->toBe('Juan Actualizado');
    expect($fresh->department_id)->toBe($originalDept);
    expect($fresh->position_id)->toBe($originalPos);
    expect($fresh->team_id)->toBe($originalTeam);
    expect($fresh->parent_id)->toBe($originalParent);
});

test('is_active = false se actualiza correctamente (no es null)', function () {
    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'is_active' => false,
    ]));

    expect($this->employee->fresh()->is_active)->toBeFalse();
});

test('is_manager = false se actualiza correctamente', function () {
    $this->employee->update(['is_manager' => true]);

    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'is_manager' => false,
    ]));

    expect($this->employee->fresh()->is_manager)->toBeFalse();
});

test('metadata se puede actualizar a array vacío o null', function () {
    $this->employee->update(['metadata' => ['key' => 'value']]);

    $action = new UpdateEmployeeAction;
    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'metadata' => [],
    ]));

    expect($this->employee->fresh()->metadata)->toBe([]);

    $action->execute($this->employee, UpdateEmployeeDTO::fromArray([
        'metadata' => null,
    ]));

    expect($this->employee->fresh()->metadata)->toBeNull();
});
