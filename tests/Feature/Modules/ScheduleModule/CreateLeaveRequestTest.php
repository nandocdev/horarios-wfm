<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\RequestLeave;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

test('operator can create a full day leave request', function () {
    // 1. Setup
    $user = User::factory()->create();
    $user->assignRole('operator');
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // Setup schedule for full day request (Needed by RequestLeave::submit)
    $date = now()->addDays(5);
    $monday = $date->copy()->startOfWeek();
    
    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => $monday->toDateString(),
        'week_end_date' => $monday->copy()->addDays(6)->toDateString(),
        'status' => 'published',
    ]);

    $schedule = Schedule::create([
        'name' => 'Turno 8-16',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'total_minutes' => 480,
    ]);

    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id,
        'employee_id' => $employee->id,
        'day_of_week' => $date->dayOfWeekIso,
        'schedule_id' => $schedule->id,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);

    $this->actingAs($user);

    // 2. Test Livewire Component (RequestLeave es el nuevo componente consolidado)
    Livewire::test(RequestLeave::class, ['type' => 'quarterly'])
        ->set('date', $date->toDateString())
        ->set('isFullDay', true)
        ->set('reason', 'Necesito permiso por motivos personales (más de 10 caracteres)')
        ->call('submit')
        ->assertHasNoErrors();

    // 3. Assert database state
    $this->assertDatabaseHas('leave_requests', [
        'employee_id' => $employee->id,
        'status' => 'pending',
        'type' => 'quarterly',
    ]);
});
