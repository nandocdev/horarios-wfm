<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Actions\AssignIntradayActivityAction;
use App\Modules\WfmModule\Actions\CreateApprovedIntradayPeriodAction;
use App\Modules\WfmModule\DTOs\IntradayActivityDTO;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// -------------------------------------------------------------------------
// Setup helper
// -------------------------------------------------------------------------
function setupIntradayFixtures(): array
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'wfm', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'wfm.intraday.periods.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'wfm.intraday.assign', 'guard_name' => 'web']);
    $role->givePermissionTo(['wfm.intraday.periods.manage', 'wfm.intraday.assign']);
    $user->assignRole('wfm');

    $team = Team::factory()->create(['is_active' => true]);
    $employee = Employee::factory()->create(['user_id' => $user->id, 'team_id' => $team->id]);

    $activityType = ActivityType::create([
        'name' => 'Coaching',
        'color' => '#6366f1',
        'is_productive' => false,
        'is_paid' => true,
    ]);

    $definition = ScheduledActivityDefinition::create([
        'name' => 'Sesión de Coaching',
        'activity_type_id' => $activityType->id,
        'default_duration_minutes' => 30,
        'is_active' => true,
    ]);

    return compact('user', 'team', 'employee', 'activityType', 'definition');
}

// -------------------------------------------------------------------------
// Test 1: WFM puede crear un periodo aprobado
// -------------------------------------------------------------------------
it('wfm can create an approved intraday period', function () {
    ['team' => $team, 'definition' => $definition] = setupIntradayFixtures();

    $action = app(CreateApprovedIntradayPeriodAction::class);
    $period = $action->execute([
        'team_id'               => $team->id,
        'activity_definition_id' => $definition->id,
        'date'                  => now()->toDateString(),
        'start_time'            => '10:00',
        'end_time'              => '10:30',
        'max_slots'             => 3,
        'notes'                 => 'Sesión de coaching matutina',
    ]);

    expect($period)->toBeInstanceOf(ApprovedIntradayPeriod::class);
    $this->assertDatabaseHas('approved_intraday_periods', [
        'team_id'   => $team->id,
        'max_slots' => 3,
        'start_time' => '10:00',
        'end_time'  => '10:30',
    ]);
});

// -------------------------------------------------------------------------
// Test 2: CreateApprovedIntradayPeriodAction rechaza end_time <= start_time
// -------------------------------------------------------------------------
it('rejects period creation when end_time is before start_time', function () {
    ['team' => $team, 'definition' => $definition] = setupIntradayFixtures();

    app(CreateApprovedIntradayPeriodAction::class)->execute([
        'team_id'               => $team->id,
        'activity_definition_id' => $definition->id,
        'date'                  => now()->toDateString(),
        'start_time'            => '11:00',
        'end_time'              => '10:00', // Fin antes de inicio
        'max_slots'             => 1,
    ]);
})->throws(ValidationException::class);

// -------------------------------------------------------------------------
// Test 3: Asignación correcta dentro de un periodo aprobado
// -------------------------------------------------------------------------
it('coordinator can assign an employee to an approved period slot', function () {
    ['team' => $team, 'employee' => $employee, 'definition' => $definition, 'activityType' => $activityType]
        = setupIntradayFixtures();

    $period = ApprovedIntradayPeriod::create([
        'team_id'               => $team->id,
        'activity_definition_id' => $definition->id,
        'date'                  => now()->toDateString(),
        'start_time'            => '10:00',
        'end_time'              => '10:30',
        'max_slots'             => 5,
    ]);

    $dto = IntradayActivityDTO::fromArray([
        'activity_definition_id' => $definition->id,
        'employee_ids'           => [$employee->id],
        'date'                   => now()->toDateString(),
        'start_time'             => '10:00',
        'end_time'               => '10:30',
        'notes'                  => null,
        'approved_period_id'     => $period->id,
    ]);

    $created = app(AssignIntradayActivityAction::class)->execute($dto);

    expect($created)->toHaveCount(1);
    $this->assertDatabaseHas('intraday_activities', [
        'employee_id'        => $employee->id,
        'activity_type_id'   => $activityType->id,
        'approved_period_id' => $period->id,
    ]);
});

// -------------------------------------------------------------------------
// Test 4: Rechaza asignación cuando se excede max_slots
// -------------------------------------------------------------------------
it('rejects assignment when max_slots are exceeded', function () {
    ['team' => $team, 'employee' => $employee, 'definition' => $definition]
        = setupIntradayFixtures();

    // Crear un segundo empleado del mismo equipo
    $employee2 = Employee::factory()->create(['team_id' => $team->id]);

    $period = ApprovedIntradayPeriod::create([
        'team_id'               => $team->id,
        'activity_definition_id' => $definition->id,
        'date'                  => now()->toDateString(),
        'start_time'            => '10:00',
        'end_time'              => '10:30',
        'max_slots'             => 1, // Solo 1 slot disponible
    ]);

    // Intentar asignar 2 empleados a un periodo de 1 slot
    $dto = IntradayActivityDTO::fromArray([
        'activity_definition_id' => $definition->id,
        'employee_ids'           => [$employee->id, $employee2->id],
        'date'                   => now()->toDateString(),
        'start_time'             => '10:00',
        'end_time'               => '10:30',
        'approved_period_id'     => $period->id,
    ]);

    app(AssignIntradayActivityAction::class)->execute($dto);
})->throws(ValidationException::class);

// -------------------------------------------------------------------------
// Test 5: Rechaza asignación de empleado de otro equipo
// -------------------------------------------------------------------------
it('rejects assignment when employee does not belong to the approved period team', function () {
    ['team' => $team, 'definition' => $definition]
        = setupIntradayFixtures();

    $otherTeam = Team::factory()->create(['is_active' => true]);
    $foreignEmployee = Employee::factory()->create(['team_id' => $otherTeam->id]);

    $period = ApprovedIntradayPeriod::create([
        'team_id'               => $team->id,
        'activity_definition_id' => $definition->id,
        'date'                  => now()->toDateString(),
        'start_time'            => '10:00',
        'end_time'              => '10:30',
        'max_slots'             => 5,
    ]);

    $dto = IntradayActivityDTO::fromArray([
        'activity_definition_id' => $definition->id,
        'employee_ids'           => [$foreignEmployee->id],
        'date'                   => now()->toDateString(),
        'start_time'             => '10:00',
        'end_time'               => '10:30',
        'approved_period_id'     => $period->id,
    ]);

    // Solo el empleado foráneo fue rechazado, no hubo creados → ValidationException
    app(AssignIntradayActivityAction::class)->execute($dto);
})->throws(ValidationException::class);
