<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Operations;

use App\Modules\OperationsModule\Livewire\ControlTower\TeamPerformanceWidget;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamPerformanceWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Livewire::withoutLazyLoading();
    }

    public function test_widget_shows_empty_state_when_no_metrics(): void
    {
        $team = Team::factory()->create(['name' => 'Colas A']);
        Employee::factory()->count(2)->create(['team_id' => $team->id]);

        Livewire::test(TeamPerformanceWidget::class, ['selectedDate' => '2026-08-06'])
            ->assertStatus(200)
            ->assertSee('Sin métricas para la fecha seleccionada')
            ->assertDontSee('Colas A');
    }

    public function test_widget_renders_team_metrics_when_data_exists(): void
    {
        $team = Team::factory()->create(['name' => 'Colas B']);
        $employee = Employee::factory()->create(['team_id' => $team->id]);

        AgentIntervalMetric::create([
            'employee_id' => $employee->id,
            'interval_start' => Carbon::parse('2026-08-06 08:00:00'),
            'interval_end' => Carbon::parse('2026-08-06 08:15:00'),
            'talk_seconds' => 500,
            'ready_seconds' => 100,
            'wrap_seconds' => 100,
            'calls_handled' => 10,
            'aht_seconds' => 60,
            'occupancy' => 85.5,
            'utilization' => 70.0,
            'adherence' => 90.0,
        ]);

        Livewire::test(TeamPerformanceWidget::class, ['selectedDate' => '2026-08-06'])
            ->assertStatus(200)
            ->assertSee('Colas B')
            ->assertSee('85.5%')
            ->assertSee('90%')
            ->assertDontSee('Sin métricas para la fecha seleccionada');
    }
}
