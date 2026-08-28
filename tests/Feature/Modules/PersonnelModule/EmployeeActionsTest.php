<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Actions\CreateEmployeeAction;
use App\Modules\PersonnelModule\Actions\DeleteEmployeeAction;
use App\Modules\PersonnelModule\Actions\UpdateEmployeeAction;
use App\Modules\PersonnelModule\DTOs\CreateEmployeeDTO;
use App\Modules\PersonnelModule\DTOs\UpdateEmployeeDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;

beforeEach(function () {
    $this->directorate = Directorate::create(['name' => 'Dir Test']);
    $this->department = Department::create(['directorate_id' => $this->directorate->id, 'name' => 'Dept Test']);
    $this->position = Position::create(['department_id' => $this->department->id, 'name' => 'Pos Test', 'position_code' => 'PT-001']);
    $this->status = EmploymentStatus::create(['name' => 'Activo', 'code' => 'ACT']);
    $this->user = User::factory()->create();
});

test('crea un empleado exitosamente', function () {
    $employee = (new CreateEmployeeAction)->execute(
        new CreateEmployeeDTO(
            employee_number: 'EMP001',
            username: 'jperez',
            first_name: 'Juan',
            last_name: 'Pérez',
            email: 'juan@example.com',
            birth_date: '1990-01-01',
            gender: 'M',
            department_id: $this->department->id,
            position_id: $this->position->id,
            employment_status_id: $this->status->id,
            user_id: $this->user->id,
            hire_date: '2024-01-01',
            is_active: true,
        )
    );

    expect($employee)->toBeInstanceOf(Employee::class);
    expect($employee->employee_number)->toBe('EMP001');
    expect($employee->is_active)->toBeTrue();
    expect($employee->full_name)->toBe('Juan Pérez');
});

test('actualiza un empleado exitosamente', function () {
    $employee = (new CreateEmployeeAction)->execute(
        new CreateEmployeeDTO(
            employee_number: 'EMP002',
            username: 'mgarcia',
            first_name: 'María',
            last_name: 'García',
            email: 'maria@example.com',
            birth_date: '1985-05-15',
            gender: 'F',
            department_id: $this->department->id,
            position_id: $this->position->id,
            employment_status_id: $this->status->id,
            user_id: $this->user->id,
            hire_date: '2024-01-01',
            is_active: true,
        )
    );

    $updated = (new UpdateEmployeeAction)->execute(
        $employee,
        UpdateEmployeeDTO::fromArray([
            'first_name' => 'María Actualizada',
            'last_name' => 'García',
            'email' => 'maria.nueva@example.com',
            'hire_date' => '2024-01-01',
            'is_active' => false,
            'is_manager' => true,
        ])
    );

    expect($updated->first_name)->toBe('María Actualizada');
    expect($updated->is_active)->toBeFalse();
    expect($updated->is_manager)->toBeTrue();
});

test('elimina un empleado con soft delete', function () {
    $employee = (new CreateEmployeeAction)->execute(
        new CreateEmployeeDTO(
            employee_number: 'EMP003',
            username: 'plopez',
            first_name: 'Pedro',
            last_name: 'López',
            email: 'pedro@example.com',
            birth_date: '1992-03-20',
            gender: 'M',
            department_id: $this->department->id,
            position_id: $this->position->id,
            employment_status_id: $this->status->id,
            user_id: $this->user->id,
            hire_date: '2024-01-01',
            is_active: true,
        )
    );

    (new DeleteEmployeeAction)->execute($employee);

    expect(Employee::find($employee->id))->toBeNull();
    expect(Employee::withTrashed()->find($employee->id))->not->toBeNull();
});

test('empleado no referencia WeeklyScheduleAssignment (cross-module)', function () {
    $methods = collect((new ReflectionClass(Employee::class))->getMethods())
        ->map(fn ($m) => $m->getName())
        ->toArray();

    expect(in_array('assignments', $methods))->toBeFalse();
});
