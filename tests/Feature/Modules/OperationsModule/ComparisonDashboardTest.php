<?php

declare(strict_types=1);

use App\Modules\AnalyticsModule\Models\DailyKpi;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Livewire\ComparisonDashboard;
use App\Modules\OperationsModule\Models\QueueDailyMetric;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('it renders comparison dashboard successfully', function () {
    Livewire::test(ComparisonDashboard::class)
        ->assertStatus(200)
        ->assertSee('Comparativos de Operaciones')
        ->assertSee('Equipos')
        ->assertSee('Seleccione elementos para comparar');
});

test('it toggles team selection and computes comparison results from daily kpis', function () {
    $teamA = Team::create(['name' => 'Team Alpha', 'is_active' => true]);
    $teamB = Team::create(['name' => 'Team Beta', 'is_active' => true]);

    DailyKpi::create([
        'evaluation_date' => now()->subDays(2)->toDateString(),
        'granularity' => 'team',
        'dim_team_id' => $teamA->id,
        'occupancy' => 85.5,
        'productivity' => 90.0,
        'total_calls' => 150,
        'service_level' => 88.0,
        'aht_seconds' => 240,
    ]);

    DailyKpi::create([
        'evaluation_date' => now()->subDays(1)->toDateString(),
        'granularity' => 'team',
        'dim_team_id' => $teamB->id,
        'occupancy' => 78.0,
        'productivity' => 85.0,
        'total_calls' => 200,
        'service_level' => 92.5,
        'aht_seconds' => 210,
    ]);

    Livewire::test(ComparisonDashboard::class)
        ->call('toggleId', $teamA->id)
        ->call('toggleId', $teamB->id)
        ->assertSee('Team Alpha')
        ->assertSee('Team Beta')
        ->assertSee('Tabla Comparativa de Desempeño')
        ->assertSee('85.5%')
        ->assertSee('78.0%');
});

test('it switches dimension and computes queue comparison correctly', function () {
    $queue = CallQueue::create([
        'name' => 'Soporte VIP',
        'finesse_id' => 9991,
        'is_active' => true,
    ]);

    QueueDailyMetric::create([
        'queue_id' => $queue->id,
        'metric_date' => now()->subDays(1)->toDateString(),
        'offered_calls' => 100,
        'handled_calls' => 95,
        'abandoned_calls' => 5,
        'sl_calls' => 85,
        'total_talk_seconds' => 19000,
        'total_work_seconds' => 1900,
        'total_hold_seconds' => 950,
        'total_wait_seconds' => 1425,
    ]);

    Livewire::test(ComparisonDashboard::class)
        ->set('dimension', 'queue')
        ->assertSet('selectedIds', [])
        ->call('toggleId', $queue->id)
        ->assertSee('Soporte VIP')
        ->assertSee('Tabla Comparativa de Desempeño');
});

test('it supports select all and deselect all actions', function () {
    $teamA = Team::create(['name' => 'Team One', 'is_active' => true]);
    $teamB = Team::create(['name' => 'Team Two', 'is_active' => true]);

    Livewire::test(ComparisonDashboard::class)
        ->call('selectAll', [$teamA->id, $teamB->id])
        ->assertCount('selectedIds', 2)
        ->call('deselectAll')
        ->assertCount('selectedIds', 0);
});
