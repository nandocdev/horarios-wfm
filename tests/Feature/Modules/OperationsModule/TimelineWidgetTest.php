<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OperationsModule;

use App\Modules\OperationsModule\Alerts\Models\AlertEvent;
use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use App\Modules\OperationsModule\Livewire\ControlTower\TimelineWidget;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::withoutLazyLoading();

    $this->today = Carbon::today();

    $this->rule = AlertRule::create([
        'event_type' => 'adherence.alert',
        'label' => 'Alerta de Adherencia',
        'description' => 'Agente fuera de adherencia.',
    ]);
});

it('renders today persisted alert events chronologically with level colors', function () {
    AlertEvent::create([
        'alert_rule_id' => $this->rule->id,
        'message' => 'Agente X fuera de adherencia 6 min',
        'level' => 'critical',
        'source' => 'test',
        'first_triggered_at' => $this->today->copy()->setTime(9, 15),
        'last_triggered_at' => $this->today->copy()->setTime(9, 15),
    ]);

    AlertEvent::create([
        'alert_rule_id' => $this->rule->id,
        'message' => 'Descanso excedido Agente Y',
        'level' => 'warning',
        'source' => 'test',
        'first_triggered_at' => $this->today->copy()->setTime(10, 5),
        'last_triggered_at' => $this->today->copy()->setTime(10, 5),
    ]);

    // Evento de ayer: no debe aparecer en el timeline de hoy.
    AlertEvent::create([
        'alert_rule_id' => $this->rule->id,
        'message' => 'Evento de ayer',
        'level' => 'critical',
        'source' => 'test',
        'first_triggered_at' => $this->today->copy()->subDay()->setTime(8, 0),
        'last_triggered_at' => $this->today->copy()->subDay()->setTime(8, 0),
    ]);

    Livewire::test(TimelineWidget::class, ['selectedDate' => $this->today->toDateString()])
        ->assertStatus(200)
        ->assertSeeInOrder(['10:05', 'Descanso excedido Agente Y', '09:15', 'Agente X fuera de adherencia 6 min'])
        ->assertSee('bg-red-500', false)
        ->assertSee('bg-yellow-500', false)
        ->assertDontSee('Evento de ayer')
        ->assertDontSee('Sin eventos relevantes');
});

it('shows the empty state when no events exist for the selected date', function () {
    Livewire::test(TimelineWidget::class, ['selectedDate' => $this->today->toDateString()])
        ->assertStatus(200)
        ->assertSee('Sin eventos relevantes');
});

it('falls back to the rule label when the event has no message', function () {
    AlertEvent::create([
        'alert_rule_id' => $this->rule->id,
        'message' => '',
        'level' => 'info',
        'source' => 'test',
        'first_triggered_at' => $this->today->copy()->setTime(11, 0),
        'last_triggered_at' => $this->today->copy()->setTime(11, 0),
    ]);

    Livewire::test(TimelineWidget::class, ['selectedDate' => $this->today->toDateString()])
        ->assertStatus(200)
        ->assertSee('Alerta de Adherencia');
});
