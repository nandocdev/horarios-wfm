<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Livewire\PerformanceScorecard;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->user->assignRole($adminRole);
    $this->actingAs($this->user);
});

test('it renders performance scorecard component without errors', function () {
    $directorate = Directorate::firstOrCreate(['name' => 'Operaciones']);
    $department = Department::firstOrCreate(
        ['name' => 'Contact Center'],
        ['directorate_id' => $directorate->id]
    );

    $status = EmploymentStatus::firstOrCreate(['code' => 'ACT'], ['name' => 'Activo']);
    $position = Position::firstOrCreate(
        ['position_code' => 'P00001'],
        ['name' => 'Agente Telefónico', 'department_id' => $department->id]
    );

    $employee = Employee::create([
        'user_id' => $this->user->id,
        'employee_number' => 'EMP-TEST-01',
        'username' => 'agent01',
        'first_name' => 'Carlos',
        'last_name' => 'Gomez',
        'email' => 'carlos@example.com',
        'employment_status_id' => $status->id,
        'position_id' => $position->id,
        'hire_date' => '2024-01-01',
        'is_active' => true,
    ]);

    Livewire::test(PerformanceScorecard::class)
        ->assertStatus(200)
        ->assertSee('Desempeño')
        ->assertSee('Análisis de adherencia y productividad de Contact Center')
        ->set('employeeId', $employee->id)
        ->assertSee('Carlos Gomez');
});
