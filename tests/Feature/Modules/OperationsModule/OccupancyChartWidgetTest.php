<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OperationsModule;

use App\Modules\OperationsModule\Livewire\ControlTower\OccupancyChartWidget;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::withoutLazyLoading();
});

it('builds hourly series for today and yesterday from interval metrics', function () {
    $employee = Employee::factory()->create();
    $today = Carbon::today();

    foreach ([8, 9] as $hour) {
        AgentIntervalMetric::create([
            'employee_id' => $employee->id,
            'interval_start' => $today->copy()->setTime($hour, 0),
            'interval_end' => $today->copy()->setTime($hour, 15),
            'occupancy' => 80.0 + $hour,
            'adherence' => 90.0,
        ]);
    }

    $yesterday = $today->copy()->subDay();
    AgentIntervalMetric::create([
        'employee_id' => $employee->id,
        'interval_start' => $yesterday->copy()->setTime(8, 0),
        'interval_end' => $yesterday->copy()->setTime(8, 15),
        'occupancy' => 70.0,
        'adherence' => 88.0,
    ]);

    $component = Livewire::test(OccupancyChartWidget::class, ['selectedDate' => $today->toDateString()]);

    $component->assertStatus(200)
        ->assertViewHas('hasData', true)
        ->assertViewHas('chartOptions', function (array $options): bool {
            [$todaySeries, $yesterdaySeries] = array_column($options['series'], 'data');

            // Índices 8 y 9 corresponden a las horas 08:00 y 09:00 del rango 6-17.
            return $options['series'][0]['name'] === 'Hoy'
                && $todaySeries[2] === 88.0
                && $todaySeries[3] === 89.0
                && $yesterdaySeries[2] === 70.0;
        });
});

it('renders the empty state when no interval metrics exist', function () {
    Livewire::test(OccupancyChartWidget::class, ['selectedDate' => Carbon::today()->toDateString()])
        ->assertStatus(200)
        ->assertViewHas('hasData', false)
        ->assertViewHas('chartOptions', []);
});
