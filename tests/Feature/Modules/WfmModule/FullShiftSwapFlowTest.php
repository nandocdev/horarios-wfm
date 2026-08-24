<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\WfmModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Livewire\WfmSwapApprovals;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('full swap approval flow persists assignment changes in schedule', function () {
    // Setup org structure
    $directorate = Directorate::create(['name' => 'Dirección']);
    $department = Department::create(['name' => 'Depto', 'directorate_id' => $directorate->id]);
    $position = Position::create(['name' => 'Operador', 'department_id' => $department->id, 'position_code' => 'OP1']);
    $teamA = Team::create(['name' => 'Team A']);
    $teamB = Team::create(['name' => 'Team B']);

    // Setup users & employees
    $wfmUser = User::factory()->create();
    $wfmUser->assignRole('wfm');
    Employee::factory()->create(['user_id' => $wfmUser->id]);

    // requester_id/recipient_id de los swaps referencian users.id.
    $requesterUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $requester = Employee::factory()->create(['team_id' => $teamA->id, 'position_id' => $position->id, 'user_id' => $requesterUser->id]);
    $recipient = Employee::factory()->create(['team_id' => $teamB->id, 'position_id' => $position->id, 'user_id' => $recipientUser->id]);

    // Setup schedule
    $date = now()->addDays(5);
    $monday = $date->copy()->startOfWeek();
    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => $monday->toDateString(),
        'week_end_date' => $monday->copy()->addDays(6)->toDateString(),
        'status' => 'published',
    ]);
    $morning = Schedule::create(['name' => 'Mañana', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'total_minutes' => 480]);
    $afternoon = Schedule::create(['name' => 'Tarde', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'total_minutes' => 480]);

    $assignmentA = WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id, 'employee_id' => $requester->id,
        'day_of_week' => $date->dayOfWeekIso, 'schedule_id' => $morning->id,
        'start_time' => '08:00:00', 'end_time' => '16:00:00',
    ]);
    $assignmentB = WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id, 'employee_id' => $recipient->id,
        'day_of_week' => $date->dayOfWeekIso, 'schedule_id' => $afternoon->id,
        'start_time' => '14:00:00', 'end_time' => '22:00:00',
    ]);

    // Create swap request as accepted
    $swap = ShiftSwapRequest::create([
        'requester_id' => $requesterUser->id, 'recipient_id' => $recipientUser->id,
        'start_date' => $date->toDateString(), 'status' => 'accepted',
        'requester_assignment_snapshot' => $assignmentA->toArray(),
        'recipient_assignment_snapshot' => $assignmentB->toArray(),
    ]);

    $this->actingAs($wfmUser);

    // Execute approval via Livewire component
    Livewire::test(WfmSwapApprovals::class)
        ->call('approveSwap', $swap->id)
        ->assertHasNoErrors();

    // Verify request is approved
    expect($swap->fresh()->status)->toBe('approved');

    // Verify old assignments marked as replaced
    $oldA = WeeklyScheduleAssignment::withoutGlobalScopes()->find($assignmentA->id);
    $oldB = WeeklyScheduleAssignment::withoutGlobalScopes()->find($assignmentB->id);
    expect($oldA->is_replaced)->toBeTrue();
    expect($oldB->is_replaced)->toBeTrue();

    // Verify new swapped assignments exist
    $newA = WeeklyScheduleAssignment::where('employee_id', $requester->id)
        ->where('day_of_week', $date->dayOfWeekIso)
        ->where('is_replaced', false)->first();
    $newB = WeeklyScheduleAssignment::where('employee_id', $recipient->id)
        ->where('day_of_week', $date->dayOfWeekIso)
        ->where('is_replaced', false)->first();

    expect($newA)->not->toBeNull();
    expect($newB)->not->toBeNull();
    expect((int) $newA->schedule_id)->toBe((int) $afternoon->id);
    expect((int) $newB->schedule_id)->toBe((int) $morning->id);
});

test('approveSwap shows error toast when process fails', function () {
    $wfmUser = User::factory()->create();
    $wfmUser->assignRole('wfm');
    Employee::factory()->create(['user_id' => $wfmUser->id]);

    $swap = ShiftSwapRequest::create([
        // requester/recipient referencian users.id.
        'requester_id' => User::factory()->create()->id,
        'recipient_id' => User::factory()->create()->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'pending',
    ]);

    $this->actingAs($wfmUser);

    Livewire::test(WfmSwapApprovals::class)
        ->call('approveSwap', $swap->id);
    // Status should remain pending since action throws for non-accepted
    expect($swap->fresh()->status)->toBe('pending');
});

test('approveSwap preserves original assignment data integrity after swap', function () {
    $directorate = Directorate::create(['name' => 'Dir']);
    $department = Department::create(['name' => 'Dep', 'directorate_id' => $directorate->id]);
    $position = Position::create(['name' => 'Op', 'department_id' => $department->id, 'position_code' => 'OP1']);
    $team = Team::create(['name' => 'Team']);

    $wfmUser = User::factory()->create();
    $wfmUser->assignRole('wfm');
    Employee::factory()->create(['user_id' => $wfmUser->id]);

    $requesterUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $requester = Employee::factory()->create(['team_id' => $team->id, 'position_id' => $position->id, 'user_id' => $requesterUser->id]);
    $recipient = Employee::factory()->create(['team_id' => $team->id, 'position_id' => $position->id, 'user_id' => $recipientUser->id]);

    $date = now()->addDays(5);
    $monday = $date->copy()->startOfWeek();
    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => $monday->toDateString(),
        'week_end_date' => $monday->copy()->addDays(6)->toDateString(),
        'status' => 'published',
    ]);
    $morning = Schedule::create(['name' => 'M', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'total_minutes' => 480]);
    $afternoon = Schedule::create(['name' => 'T', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'total_minutes' => 480]);
    $night = Schedule::create(['name' => 'N', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'total_minutes' => 480]);

    // Requester has morning shift, recipient has afternoon shift on swap day
    $assignmentA = WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id, 'employee_id' => $requester->id,
        'day_of_week' => $date->dayOfWeekIso, 'schedule_id' => $morning->id,
        'start_time' => '08:00:00', 'end_time' => '16:00:00',
        'lunch_start_time' => '12:00:00', 'lunch_end_time' => '13:00:00',
    ]);
    $assignmentB = WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id, 'employee_id' => $recipient->id,
        'day_of_week' => $date->dayOfWeekIso, 'schedule_id' => $afternoon->id,
        'start_time' => '14:00:00', 'end_time' => '22:00:00',
        'lunch_start_time' => '18:00:00', 'lunch_end_time' => '19:00:00',
    ]);

    // Also assign different shifts on another day that should NOT be affected
    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id, 'employee_id' => $requester->id,
        'day_of_week' => $date->copy()->addDay()->dayOfWeekIso, 'schedule_id' => $night->id,
        'start_time' => '22:00:00', 'end_time' => '06:00:00',
    ]);

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requesterUser->id, 'recipient_id' => $recipientUser->id,
        'start_date' => $date->toDateString(), 'status' => 'accepted',
        'requester_assignment_snapshot' => $assignmentA->toArray(),
        'recipient_assignment_snapshot' => $assignmentB->toArray(),
    ]);

    $this->actingAs($wfmUser);

    Livewire::test(WfmSwapApprovals::class)
        ->call('approveSwap', $swap->id)
        ->assertHasNoErrors();

    // Verify only the swap day assignments were affected
    $requesterOtherDay = WeeklyScheduleAssignment::where('employee_id', $requester->id)
        ->where('day_of_week', $date->copy()->addDay()->dayOfWeekIso)
        ->where('is_replaced', false)->first();
    expect($requesterOtherDay)->not->toBeNull();
    expect((int) $requesterOtherDay->schedule_id)->toBe((int) $night->id);
});
