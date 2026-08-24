<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OperationsModule;

use App\Modules\OperationsModule\Actions\GenerateAgentIntervalMetricsAction;
use App\Modules\OperationsModule\Jobs\AggregateIntervalMetricsJob;
use App\Modules\OperationsModule\Livewire\ControlTower\TeamPerformanceWidget;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::withoutLazyLoading();

    $this->today = Carbon::today();
    $this->dayOfWeek = $this->today->dayOfWeekIso;
});

it('generates interval metrics when the week is published and the widget renders them', function () {
    $team = Team::create(['name' => 'Torre Control', 'is_active' => true]);
    $employee = Employee::factory()->create(['team_id' => $team->id]);

    $schedule = Schedule::factory()->create([
        'start_time' => '00:00:00',
        'end_time' => '23:59:00',
    ]);

    $weekStart = $this->today->copy()->startOfWeek();
    DB::table('weekly_schedules')->insert([
        'week_start_date' => $weekStart->toDateString(),
        'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
        'status' => 'published',
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $wsId = (int) DB::getPdo()->lastInsertId();

    DB::table('weekly_schedule_assignments')->insert([
        'weekly_schedule_id' => $wsId,
        'employee_id' => $employee->id,
        'schedule_id' => $schedule->id,
        'day_of_week' => $this->dayOfWeek,
        'start_time' => '00:00:00',
        'end_time' => '23:59:00',
        'is_replaced' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Replicar el cálculo del job: último intervalo cerrado de 15 minutos.
    $now = Carbon::now()->startOfMinute();
    $intervalModulus = $now->minute % 15;
    $intervalStart = $now->subMinutes($intervalModulus + 15)->second(0);

    // Transición previa al intervalo: el agente queda TALKING durante todo él.
    DB::table('agent_state_transitions')->insert([
        'employee_id' => $employee->id,
        'agent_login_id' => 'TEST-LOGIN-1',
        'agent_state' => 'TALKING',
        'transition_time' => $intervalStart->copy()->subMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // El job calcula el último intervalo cerrado de 15 minutos.
    (new AggregateIntervalMetricsJob)->handle(app(GenerateAgentIntervalMetricsAction::class));

    $metric = AgentIntervalMetric::where('employee_id', $employee->id)
        ->whereDate('interval_start', $this->today->toDateString())
        ->first();

    expect($metric)->not->toBeNull()
        ->and((int) $metric->interval_start->diffInMinutes($metric->interval_end))->toBe(15);

    // El widget ahora debe renderizar el equipo con datos.
    Livewire::test(TeamPerformanceWidget::class, ['selectedDate' => $this->today->toDateString()])
        ->assertStatus(200)
        ->assertSee('Torre Control')
        ->assertDontSee('Sin métricas para la fecha seleccionada');
});

it('generates no metrics when the week is not planned', function () {
    $employee = Employee::factory()->create();

    (new AggregateIntervalMetricsJob)->handle(app(GenerateAgentIntervalMetricsAction::class));

    expect(AgentIntervalMetric::where('employee_id', $employee->id)->exists())->toBeFalse();
});
