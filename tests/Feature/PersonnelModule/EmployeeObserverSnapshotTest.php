<?php

declare(strict_types=1);

use App\Modules\AnalyticsModule\Models\EmployeeSnapshot;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Carbon\Carbon;

beforeEach(function () {
    $this->directorate = Directorate::create(['name' => 'Dir Test']);
    $this->department = Department::create(['directorate_id' => $this->directorate->id, 'name' => 'Dept Test']);
    $this->position = Position::create(['department_id' => $this->department->id, 'name' => 'Pos Test', 'position_code' => 'PT-001']);
    $this->status = EmploymentStatus::create(['name' => 'Activo', 'code' => 'ACT']);

    $this->employee = Employee::create([
        'employee_number' => 'EMP001',
        'username' => 'emp001',
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'jperez@example.com',
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'employment_status_id' => $this->status->id,
        'hire_date' => '2024-01-01',
        'is_active' => true,
    ]);
});

test('created crea un snapshot SCD2 inicial', function () {
    expect(EmployeeSnapshot::where('employee_id', $this->employee->id)->count())->toBe(1);

    $snapshot = EmployeeSnapshot::where('employee_id', $this->employee->id)->first();
    expect($snapshot->is_current)->toBeTrue();
    expect($snapshot->valid_from->toDateString())->toBe(Carbon::now()->startOfDay()->toDateString());
    expect($snapshot->valid_to)->toBeNull();
});

test('updated con cambios organizacionales crea nuevo snapshot y cierra anterior', function () {
    $initialCount = EmployeeSnapshot::where('employee_id', $this->employee->id)->count();
    expect($initialCount)->toBe(1);

    $this->employee->update(['department_id' => null]);

    $snapshots = EmployeeSnapshot::where('employee_id', $this->employee->id)->orderBy('valid_from')->get();
    expect($snapshots->count())->toBe(2);

    // Primer snapshot cerrado
    expect($snapshots[0]->is_current)->toBeFalse();
    expect($snapshots[0]->valid_to)->not->toBeNull();

    // Segundo snapshot actual
    expect($snapshots[1]->is_current)->toBeTrue();
    expect($snapshots[1]->department_id)->toBeNull();
});

test('updated SIN cambios organizacionales NO crea snapshot duplicado', function () {
    $this->employee->update(['phone' => '+56912345678']); // No es cambio organizacional

    expect(EmployeeSnapshot::where('employee_id', $this->employee->id)->count())->toBe(1);
});

test('deleted marca snapshot actual como inactivo', function () {
    $this->employee->delete();

    $snapshot = EmployeeSnapshot::where('employee_id', $this->employee->id)->first();
    expect($snapshot->is_current)->toBeFalse();
    expect($snapshot->valid_to)->not->toBeNull();
});

test('restored crea nuevo snapshot con estado actual', function () {
    $this->employee->delete();
    $this->employee->restore();

    $snapshots = EmployeeSnapshot::where('employee_id', $this->employee->id)->orderBy('valid_from')->get();
    expect($snapshots->count())->toBe(2);

    $latest = $snapshots->last();
    expect($latest->is_current)->toBeTrue();
    expect($latest->department_id)->toBe($this->department->id);
});

test('forceDeleted elimina todos los snapshots', function () {
    $this->employee->delete();
    $this->employee->forceDelete();

    expect(EmployeeSnapshot::where('employee_id', $this->employee->id)->count())->toBe(0);
});

test('múltiples updates consecutivos con cambios crean snapshots SCD2', function () {
    $this->employee->update(['department_id' => null]);
    $this->employee->update(['position_id' => null]);
    $this->employee->update(['team_id' => null]);

    expect(EmployeeSnapshot::where('employee_id', $this->employee->id)->count())->toBe(4);

    $latest = EmployeeSnapshot::where('employee_id', $this->employee->id)->where('is_current', true)->first();
    expect($latest->department_id)->toBeNull();
    expect($latest->position_id)->toBeNull();
});
