<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OperationsModule\ControlTower;

use App\Modules\ConnectModule\Models\CsqRealtimeStat;
use App\Modules\OperationsModule\Livewire\ControlTower\QueueTableWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->today = Carbon::today();

    $this->queueId = (int) DB::table('call_queues')->insertGetId([
        'name' => 'Soporte',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('shows the daily accumulated from CUIC counters when call records lag behind', function () {
    CsqRealtimeStat::create([
        'csq_name' => 'Soporte',
        'calls_waiting' => 2,
        'total_calls_since_midnight' => 10,
        'calls_handled_since_midnight' => 7,
        'calls_abandoned_since_midnight' => 3,
        'service_level_long_term' => 85.0,
    ]);

    $component = Livewire::test(QueueTableWidget::class)
        ->set('selectedDate', $this->today->toDateString());

    $queues = $component->viewData('queues');

    expect($queues)->toHaveCount(1)
        ->and($queues[0]['name'])->toBe('Soporte')
        ->and($queues[0]['recibidas'])->toBe(10)
        ->and($queues[0]['atendidas'])->toBe(7)
        ->and($queues[0]['abandonadas'])->toBe(3)
        ->and($queues[0]['espera'])->toBe(2);
});

it('prefers call records when they exceed the CUIC midnight counters', function () {
    CsqRealtimeStat::create([
        'csq_name' => 'Soporte',
        'total_calls_since_midnight' => 2,
        'calls_handled_since_midnight' => 1,
        'calls_abandoned_since_midnight' => 0,
    ]);

    foreach ([[2, 30], [2, 10], [1, 60]] as [$disposition, $queueTime]) {
        DB::table('call_records')->insert([
            'cisco_call_id' => uniqid(),
            'queue_id' => $this->queueId,
            'phone_number' => '5550001',
            'ivr_started_at' => $this->today->copy()->setTime(9, 0),
            'contact_disposition' => $disposition,
            'talk_time' => 100,
            'work_time' => 20,
            'queue_time' => $queueTime,
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $component = Livewire::test(QueueTableWidget::class)
        ->set('selectedDate', $this->today->toDateString());

    $queues = $component->viewData('queues');

    expect($queues)->toHaveCount(1)
        ->and($queues[0]['recibidas'])->toBe(3)
        ->and($queues[0]['atendidas'])->toBe(2)
        ->and($queues[0]['abandonadas'])->toBe(1)
        ->and($queues[0]['sla'])->toBeGreaterThan(0);
});

it('ignores midnight counters for past dates', function () {
    $yesterday = $this->today->copy()->subDay();

    CsqRealtimeStat::create([
        'csq_name' => 'Soporte',
        'total_calls_since_midnight' => 10,
        'calls_handled_since_midnight' => 7,
        'calls_abandoned_since_midnight' => 3,
    ]);

    $component = Livewire::test(QueueTableWidget::class)
        ->set('selectedDate', $yesterday->toDateString());

    $queues = $component->viewData('queues');

    expect($queues)->toBeEmpty();
});
