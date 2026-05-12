<?php

declare(strict_types=1);

use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\OrganizationModule\Models\Team;
use App\Modules\SchedulingModule\Actions\ValidatePreparationAction;
use App\Modules\SchedulingModule\DTOs\PreparationValidationDTO;
use App\Modules\SchedulingModule\Models\BreakTemplate;
use App\Modules\SchedulingModule\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('valida preparación cuando todo está correcto', function () {
    // Crear datos básicos manualmente
    // Crear directorate primero
    $directorate = Directorate::create([
        'name' => 'Test Directorate',
        'description' => 'Test directorate',
    ]);

    // Crear department
    $department = Department::create([
        'directorate_id' => $directorate->id,
        'name' => 'Test Department',
        'description' => 'Test department',
        'is_active' => true,
    ]);

    // Crear position
    $position = Position::create([
        'department_id' => $department->id,
        'name' => 'Test Position',
        'position_code' => 'TEST001',
        'description' => 'Test position',
        'is_active' => true,
    ]);

    // Crear team
    $team = Team::factory()->create();

    // Crear schedule y break template
    $schedule = Schedule::create([
        'name' => 'Test Schedule',
        'description' => 'Test schedule for validation',
        'is_active' => true,
        'start_time' => '08:00',
        'end_time' => '17:00',
        'break_duration' => 60,
    ]);

    BreakTemplate::create([
        'schedule_id' => $schedule->id,
        'name' => 'Test Break',
        'start_time' => '12:00',
        'end_time' => '13:00',
        'duration_minutes' => 60,
        'is_paid' => true,
    ]);

    // Crear empleado con todos los datos
    Employee::factory()->create([
        'is_active' => true,
        'team_id' => $team->id,
        'position_id' => $position->id,
    ]);

    // Ejecutar validación
    $action = new ValidatePreparationAction;
    $result = $action->execute();

    // Verificar que es válido
    expect($result)->toBeInstanceOf(PreparationValidationDTO::class);
    expect($result->isReady)->toBeTrue();
    expect($result->blockingIssues)->toBeEmpty();
});

it('detecta empleados sin equipo como problema bloqueante', function () {
    // Crear datos básicos
    $directorate = Directorate::create([
        'name' => 'Test Directorate 2',
        'description' => 'Test directorate',
    ]);

    $department = Department::create([
        'directorate_id' => $directorate->id,
        'name' => 'Test Department 2',
        'description' => 'Test department',
        'is_active' => true,
    ]);

    $position = Position::create([
        'department_id' => $department->id,
        'name' => 'Test Position 2',
        'position_code' => 'TEST002',
        'description' => 'Test position',
        'is_active' => true,
    ]);

    // Crear empleado sin equipo
    Employee::factory()->create([
        'is_active' => true,
        'team_id' => null,
        'position_id' => $position->id,
    ]);

    $action = new ValidatePreparationAction;
    $result = $action->execute();

    expect($result->isReady)->toBeFalse();
    expect($result->blockingIssues)->toHaveCount(1);
    expect($result->blockingIssues[0]['type'])->toBe('employees_without_team');
});

it('detecta empleados sin posición como problema bloqueante', function () {
    $team = Team::factory()->create();

    Employee::factory()->create([
        'is_active' => true,
        'team_id' => $team->id,
        'position_id' => null,
    ]);

    $action = new ValidatePreparationAction;
    $result = $action->execute();

    expect($result->isReady)->toBeFalse();
    expect($result->blockingIssues)->toHaveCount(1);
    expect($result->blockingIssues[0]['type'])->toBe('employees_without_position');
});

it('detecta falta de horarios activos como problema del catálogo', function () {
    // Crear datos básicos
    $directorate = Directorate::create([
        'name' => 'Test Directorate 3',
        'description' => 'Test directorate',
    ]);

    $department = Department::create([
        'directorate_id' => $directorate->id,
        'name' => 'Test Department 3',
        'description' => 'Test department',
        'is_active' => true,
    ]);

    $position = Position::create([
        'department_id' => $department->id,
        'name' => 'Test Position 3',
        'position_code' => 'TEST003',
        'description' => 'Test position',
        'is_active' => true,
    ]);

    $team = Team::factory()->create();

    Employee::factory()->create([
        'is_active' => true,
        'team_id' => $team->id,
        'position_id' => $position->id,
    ]);

    // No crear schedules activos ni break templates
    $action = new ValidatePreparationAction;
    $result = $action->execute();

    expect($result->catalogIssues)->toHaveCount(2); // no_active_schedules y no_break_templates
    expect($result->catalogIssues[0]['type'])->toBe('no_active_schedules');
    expect($result->catalogIssues[1]['type'])->toBe('no_break_templates');
});

it('retorna estadísticas de empleados activos', function () {
    // Crear datos básicos
    $directorate = Directorate::create([
        'name' => 'Test Directorate 4',
        'description' => 'Test directorate',
    ]);

    $department = Department::create([
        'directorate_id' => $directorate->id,
        'name' => 'Test Department 4',
        'description' => 'Test department',
        'is_active' => true,
    ]);

    $position = Position::create([
        'department_id' => $department->id,
        'name' => 'Test Position 4',
        'position_code' => 'TEST004',
        'description' => 'Test position',
        'is_active' => true,
    ]);

    $team = Team::factory()->create();

    Employee::factory()->create([
        'is_active' => true,
        'team_id' => $team->id,
        'position_id' => $position->id,
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
    ]);

    $action = new ValidatePreparationAction;
    $result = $action->execute();

    expect($result->activeEmployees)->toHaveCount(1);
    expect($result->activeEmployees[0]['name'])->toBe('Juan Pérez');
    expect($result->activeEmployees[0]['has_team'])->toBeTrue();
});
