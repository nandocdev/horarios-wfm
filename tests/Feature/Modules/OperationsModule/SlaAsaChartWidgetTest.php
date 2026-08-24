<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OperationsModule;

use App\Modules\OperationsModule\Livewire\ControlTower\SlaAsaChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::withoutLazyLoading();

    $this->today = Carbon::today();
    $this->yesterday = $this->today->copy()->subDay();
});

function sla_asa_insert_call(int $queueId, Carbon $startedAt, int $disposition, int $queueTime): void
{
    DB::table('call_records')->insert([
        'cisco_call_id' => uniqid(),
        'queue_id' => $queueId,
        'phone_number' => '5550001',
        'ivr_started_at' => $startedAt,
        'contact_disposition' => $disposition,
        'talk_time' => 100,
        'work_time' => 20,
        'queue_time' => $queueTime,
        'status' => 'closed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('builds hourly sla and asa series for today and yesterday', function () {
    $queueId = (int) DB::table('call_queues')->insertGetId([
        'name' => 'Soporte', 'created_at' => now(), 'updated_at' => now(),
    ]);

    // Hoy 08:xx: 3 ofrecidas, 2 atendidas dentro de SLA (<=20s), ASA = (10+20)/2 = 15.
    sla_asa_insert_call($queueId, $this->today->copy()->setTime(8, 5), 2, 10);
    sla_asa_insert_call($queueId, $this->today->copy()->setTime(8, 15), 2, 20);
    sla_asa_insert_call($queueId, $this->today->copy()->setTime(8, 25), 1, 90);

    // Hoy 09:xx: 1 atendida fuera de SLA (60s) -> SLA 0%, ASA 60.
    sla_asa_insert_call($queueId, $this->today->copy()->setTime(9, 10), 2, 60);

    // Ayer 08:xx: 1 atendida en SLA (5s) -> SLA 100%, ASA 5.
    sla_asa_insert_call($queueId, $this->yesterday->copy()->setTime(8, 30), 2, 5);

    Livewire::test(SlaAsaChartWidget::class, ['selectedDate' => $this->today->toDateString()])
        ->assertStatus(200)
        ->assertViewHas('hasData', true)
        ->assertViewHas('chartOptions', function (array $options): bool {
            [$slaHoy, $asaHoy, $slaAyer, $asaAyer] = array_column($options['series'], 'data');

            return $options['series'][0]['name'] === 'SLA Hoy'
                && $options['series'][1]['name'] === 'ASA Hoy (s)'
                && abs($slaHoy[2] - 66.7) < 0.1      // 2/3 atendidas <= 20s
                && $asaHoy[2] === 15.0               // (10+20)/2
                && $slaHoy[3] === 0.0                // 60s > umbral 20s
                && $asaHoy[3] === 60.0
                && $slaAyer[2] === 100.0             // 1/1 en SLA
                && $asaAyer[2] === 5.0;
        });
});

it('renders the empty state when no calls exist for either day', function () {
    Livewire::test(SlaAsaChartWidget::class, ['selectedDate' => $this->today->toDateString()])
        ->assertStatus(200)
        ->assertViewHas('hasData', false)
        ->assertViewHas('chartOptions', []);
});
