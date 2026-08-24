<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Livewire\RequestShiftSwap;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Livewire\Livewire;

test('operator can request a shift swap with another employee', function () {
    // 1. Setup Organizational Foundation
    $directorate = Directorate::create(['name' => 'Dirección de Operaciones']);
    $department = Department::create(['name' => 'Atención al Cliente', 'directorate_id' => $directorate->id]);
    $position = Position::create([
        'name' => 'Operador Asist. Serv. Aseg. I',
        'department_id' => $department->id,
        'position_code' => 'OP1',
    ]);

    $teamA = Team::create(['name' => 'Team Alpha']);
    $teamB = Team::create(['name' => 'Team Beta']);

    // 2. Setup Employees
    $user = User::factory()->create();
    $user->assignRole('operator');
    $requester = Employee::factory()->create([
        'user_id' => $user->id,
        'team_id' => $teamA->id,
        'position_id' => $position->id,
    ]);

    $otherUser = User::factory()->create();
    $recipient = Employee::factory()->create([
        'user_id' => $otherUser->id,
        'team_id' => $teamB->id,
        'position_id' => $position->id,
    ]);

    // 3. Setup Weekly Schedule and Assignments
    $date = now()->addDays(5);
    $monday = $date->copy()->startOfWeek();

    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => $monday->toDateString(),
        'week_end_date' => $monday->copy()->addDays(6)->toDateString(),
        'status' => 'published',
    ]);

    $schedule1 = Schedule::create([
        'name' => 'Turno Mañana',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'total_minutes' => 480,
    ]);

    $schedule2 = Schedule::create([
        'name' => 'Turno Tarde',
        'start_time' => '14:00:00',
        'end_time' => '22:00:00',
        'total_minutes' => 480,
    ]);

    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id,
        'employee_id' => $requester->id,
        'day_of_week' => $date->dayOfWeekIso,
        'schedule_id' => $schedule1->id,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);

    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id,
        'employee_id' => $recipient->id,
        'day_of_week' => $date->dayOfWeekIso,
        'schedule_id' => $schedule2->id,
        'start_time' => '14:00:00',
        'end_time' => '22:00:00',
    ]);

    $this->actingAs($user);

    // 4. Test Livewire Component
    Livewire::test(RequestShiftSwap::class)
        ->set('requestedDate', $date->toDateString())
        ->set('recipientId', $recipient->id)
        ->set('reason', 'Solicitud de intercambio por motivos de prueba')
        ->call('submit')
        ->assertHasNoErrors();

    // 5. Assert Database: requester/recipient almacenan users.id.
    $this->assertDatabaseHas('shift_swap_requests', [
        'requester_id' => $user->id,
        'recipient_id' => $otherUser->id,
        'status' => 'pending',
    ]);
});

test('cannot request duplicate swap for same date between same employees', function () {
    $directorate = Directorate::create(['name' => 'Dirección de Operaciones']);
    $department = Department::create(['name' => 'Atención al Cliente', 'directorate_id' => $directorate->id]);
    $position = Position::create([
        'name' => 'Operador Asist. Serv. Aseg. I',
        'department_id' => $department->id,
        'position_code' => 'OP1',
    ]);

    $teamA = Team::create(['name' => 'Team Alpha']);
    $teamB = Team::create(['name' => 'Team Beta']);

    $user = User::factory()->create();
    $user->assignRole('operator');
    $requester = Employee::factory()->create([
        'user_id' => $user->id,
        'team_id' => $teamA->id,
        'position_id' => $position->id,
    ]);

    $otherUser = User::factory()->create();
    $recipient = Employee::factory()->create([
        'user_id' => $otherUser->id,
        'team_id' => $teamB->id,
        'position_id' => $position->id,
    ]);

    $date = now()->addDays(5);
    $monday = $date->copy()->startOfWeek();

    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => $monday->toDateString(),
        'week_end_date' => $monday->copy()->addDays(6)->toDateString(),
        'status' => 'published',
    ]);

    $schedule1 = Schedule::create(['name' => 'Turno Mañana', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'total_minutes' => 480]);
    $schedule2 = Schedule::create(['name' => 'Turno Tarde', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'total_minutes' => 480]);

    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id,
        'employee_id' => $requester->id,
        'day_of_week' => $date->dayOfWeekIso,
        'schedule_id' => $schedule1->id,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);

    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id,
        'employee_id' => $recipient->id,
        'day_of_week' => $date->dayOfWeekIso,
        'schedule_id' => $schedule2->id,
        'start_time' => '14:00:00',
        'end_time' => '22:00:00',
    ]);

    ShiftSwapRequest::create([
        'requester_id' => $user->id,
        'recipient_id' => $otherUser->id,
        'start_date' => $date->toDateString(),
        'end_date' => $date->toDateString(),
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(RequestShiftSwap::class)
        ->set('requestedDate', $date->toDateString())
        ->set('recipientId', $recipient->id)
        ->set('reason', 'Intento duplicado')
        ->call('submit')
        ->assertHasErrors(['general']);
});
