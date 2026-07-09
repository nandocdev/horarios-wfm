<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Livewire\ManageIntradayActivities;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function setupWfmUser(): array
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
        'name' => 'Retroalimentación',
        'color' => '#6366f1',
        'is_productive' => false,
        'is_paid' => true,
    ]);

    $definition = ScheduledActivityDefinition::create([
        'name' => 'Sesión 1 a 1',
        'activity_type_id' => $activityType->id,
        'default_duration_minutes' => 30,
        'is_active' => true,
    ]);

    return compact('user', 'team', 'employee', 'activityType', 'definition');
}

test('wfm can create approved period and assign activity to operator via Livewire', function () {
    [
        'user' => $user,
        'team' => $team,
        'employee' => $employee,
        'definition' => $definition
    ] = setupWfmUser();

    $this->actingAs($user);

    // 1. Test creation of Approved Intraday Period
    Livewire::test(ManageIntradayActivities::class)
        ->set('periodTeamId', $team->id)
        ->set('periodActivityDefinitionId', $definition->id)
        ->set('periodDate', '2026-05-25')
        ->set('periodStartTime', '14:00')
        ->set('periodEndTime', '15:00')
        ->set('periodMaxSlots', 2)
        ->call('savePeriod')
        ->assertHasNoErrors()
        ->assertSet('showPeriodModal', false);

    $period = ApprovedIntradayPeriod::first();
    expect($period)->not->toBeNull();
    expect($period->team_id)->toBe($team->id);
    expect($period->activity_definition_id)->toBe($definition->id);
    expect($period->date->toDateString())->toBe('2026-05-25');
    expect($period->start_time)->toBe('14:00');
    expect($period->end_time)->toBe('15:00');
    expect($period->max_slots)->toBe(2);

    // 2. Test assignment of activity to employee
    Livewire::test(ManageIntradayActivities::class)
        ->set('date', '2026-05-25')
        ->call('openAssignmentModal', $period->id)
        ->assertSet('assigningPeriodId', $period->id)
        ->assertSet('startTime', '14:00')
        ->assertSet('endTime', '15:00')
        ->set('selectedEmployeeIds', [$employee->id])
        ->set('startTime', '14:00')
        ->set('endTime', '14:30')
        ->call('assignActivity')
        ->assertHasNoErrors()
        ->assertSet('showAssignmentModal', false);

    // Assert activity exists in DB
    $this->assertDatabaseHas('intraday_activities', [
        'employee_id' => $employee->id,
        'approved_period_id' => $period->id,
    ]);
});

test('assignActivity fails when times are outside min or max time boundaries', function () {
    [
        'user' => $user,
        'team' => $team,
        'employee' => $employee,
        'definition' => $definition
    ] = setupWfmUser();

    $this->actingAs($user);

    $period = ApprovedIntradayPeriod::create([
        'team_id' => $team->id,
        'activity_definition_id' => $definition->id,
        'date' => '2026-05-25',
        'start_time' => '14:00',
        'end_time' => '15:00',
        'max_slots' => 2,
    ]);

    // Test start time before period start time
    Livewire::test(ManageIntradayActivities::class)
        ->set('date', '2026-05-25')
        ->call('openAssignmentModal', $period->id)
        ->set('selectedEmployeeIds', [$employee->id])
        ->set('startTime', '13:59')
        ->set('endTime', '14:30')
        ->call('assignActivity')
        ->assertHasErrors(['startTime']);

    // Test end time after period end time
    Livewire::test(ManageIntradayActivities::class)
        ->set('date', '2026-05-25')
        ->call('openAssignmentModal', $period->id)
        ->set('selectedEmployeeIds', [$employee->id])
        ->set('startTime', '14:00')
        ->set('endTime', '15:01')
        ->call('assignActivity')
        ->assertHasErrors(['endTime']);
});
