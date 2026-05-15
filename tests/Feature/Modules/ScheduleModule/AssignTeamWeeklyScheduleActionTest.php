<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use App\Modules\WfmModule\Actions\AssignTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('assigns a schedule to an entire team for a week', function () {
    // 1. Setup
    $schedule = Schedule::create([
        'name' => 'Turno Mañana',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'total_minutes' => 480,
        'allowed_days' => [1, 2, 3, 4, 5], // Lunes a Viernes
        'is_active' => true,
    ]);

    $weekly = WeeklySchedule::create([
        'week_start_date' => '2026-04-06', // Un lunes
        'week_end_date' => '2026-04-12',
        'status' => 'draft',
    ]);

    $team = Team::create(['name' => 'Team Alpha']);
    $employee1 = Employee::factory()->create(['team_id' => $team->id]);
    $employee2 = Employee::factory()->create(['team_id' => $team->id]);

    // Importante: Crear entradas en team_members ya que la relación users() en Team las usa
    TeamMember::create(['team_id' => $team->id, 'employee_id' => $employee1->id, 'is_active' => true]);
    TeamMember::create(['team_id' => $team->id, 'employee_id' => $employee2->id, 'is_active' => true]);

    $action = app(AssignTeamWeeklyScheduleAction::class);
    
    // 2. Execute
    $action->execute(
        weeklyScheduleId: $weekly->id,
        teamId: $team->id,
        scheduleId: $schedule->id,
        lunchStart: '12:00:00',
        breakStart: '10:00:00'
    );

    // 3. Assertions
    // Verificar asignaciones de equipo (5 días permitidos)
    expect(WeeklyTeamAssignment::where('team_id', $team->id)->count())->toBe(5);
    
    // Verificar asignaciones individuales para el lunes (day 1)
    $today = now()->toDateString();
    $this->assertDatabaseHas('weekly_schedule_assignments', [
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $employee1->id,
        'day_of_week' => 1,
        'schedule_id' => $schedule->id,
        'lunch_start_time' => $today . ' 12:00:00',
    ]);

    $this->assertDatabaseHas('weekly_schedule_assignments', [
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $employee2->id,
        'day_of_week' => 1,
        'schedule_id' => $schedule->id,
    ]);

    // Verificar que el domingo (day 7) no tiene asignación porque no está en allowed_days
    $this->assertDatabaseMissing('weekly_schedule_assignments', [
        'employee_id' => $employee1->id,
        'day_of_week' => 7,
    ]);
});
