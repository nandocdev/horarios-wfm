<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Operations;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\OperationsModule\Actions\ReconcileEmployeeAttendanceAction;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Database\Seeders\IncidentTypeSeeder;
use Tests\TestCase;

class AttendanceReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IncidentTypeSeeder::class);
    }

    public function test_it_creates_late_incident_when_employee_is_late(): void
    {
        // Fijar fecha: Lunes 2026-05-11
        $date = Carbon::parse('2026-05-11');
        Carbon::setTestNow($date->copy()->setTime(19, 0, 0));

        // 1. Setup Employee and Schedule (08:00 - 17:00)
        $employee = Employee::factory()->create(['is_active' => true]);

        $schedule = Schedule::create([
            'name' => 'Turno Oficina',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'total_minutes' => 540,
            'is_active' => true,
        ]);

        $weeklySchedule = WeeklySchedule::create([
            'name' => 'Semana Test',
            'week_start_date' => '2026-05-11',
            'week_end_date' => '2026-05-17',
            'status' => 'active',
        ]);

        WeeklyScheduleAssignment::create([
            'weekly_schedule_id' => $weeklySchedule->id,
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        // 2. Setup Cisco Transitions (Late entry at 08:15)
        AgentStateTransition::create([
            'employee_id' => $employee->id,
            'agent_login_id' => 'agent1',
            'transition_time' => $date->copy()->setTime(8, 15, 0),
            'agent_state' => 'Ready',
            'duration' => 3600,
        ]);

        // 3. Run Reconciliation
        $action = app(ReconcileEmployeeAttendanceAction::class);
        $action->execute($employee, $date);

        // 4. Assert Incident Created
        $this->assertDatabaseCount('attendance_incidents', 1);

        $incident = AttendanceIncident::first();
        $this->assertEquals('LATE', $incident->type->code);
    }

    public function test_it_creates_absent_incident_when_no_marks_found(): void
    {
        // Fijar fecha: Lunes 2026-05-11
        $date = Carbon::parse('2026-05-11');
        Carbon::setTestNow($date->copy()->setTime(19, 0, 0));

        $employee = Employee::factory()->create(['is_active' => true]);

        $schedule = Schedule::create([
            'name' => 'Turno Oficina',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'total_minutes' => 540,
            'is_active' => true,
        ]);

        $weeklySchedule = WeeklySchedule::create([
            'name' => 'Semana Test',
            'week_start_date' => '2026-05-11',
            'week_end_date' => '2026-05-17',
            'status' => 'active',
        ]);

        WeeklyScheduleAssignment::create([
            'weekly_schedule_id' => $weeklySchedule->id,
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        // No transitions created

        $action = app(ReconcileEmployeeAttendanceAction::class);
        $action->execute($employee, $date);

        $this->assertDatabaseCount('attendance_incidents', 1);

        $incident = AttendanceIncident::with('type')->first();
        $this->assertNotNull($incident);
        $this->assertEquals('ABSENT', $incident->type->code);
    }
}
