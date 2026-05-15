<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Actions\AssignIntradayActivityAction;
use App\Modules\WfmModule\DTOs\IntradayActivityDTO;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('wfm can assign intraday activity to employee via Action', function () {
    // 1. Setup Auth & Permissions (Guard name web por defecto en Fortify/Sanctum)
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'wfm', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'intraday.assign', 'guard_name' => 'web']);
    $role->givePermissionTo('intraday.assign');
    $user->assignRole('wfm');

    // 2. Setup Models
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $activityType = ActivityType::create([
        'name' => 'Coaching',
        'color' => '#ff0000',
        'is_productive' => false,
        'is_paid' => true,
    ]);

    $definition = ScheduledActivityDefinition::create([
        'name' => 'Sesión de Coaching',
        'activity_type_id' => $activityType->id,
        'default_duration_minutes' => 30,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    // 3. Prepare DTO (IntradayActivityDTO es el contrato actual)
    $dto = new IntradayActivityDTO(
        activity_definition_id: (int) $definition->id,
        employee_ids: [(int) $employee->id],
        date: now()->toDateString(),
        start_time: '10:00:00',
        end_time: '10:30:00',
        notes: 'Session Note'
    );

    // 4. Execute Action
    app(AssignIntradayActivityAction::class)->execute($dto);

    // 5. Assertions (intraday_activities es la tabla de asignaciones en la nueva arquitectura)
    $this->assertDatabaseHas('intraday_activities', [
        'employee_id' => $employee->id,
        'activity_type_id' => $activityType->id,
        'notes' => 'Session Note',
    ]);
});
