<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class IntervalDashboard extends Component
{
    #[Url]
    public string $date = '';

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
    }

    public function render()
    {
        $slots = $this->buildSlots();
        $realMetrics = $this->getRealMetrics();
        $forecastMetrics = $this->getForecastMetrics();
        $callMetrics = $this->getCallMetricsByInterval();

        $rows = collect($slots)->map(function ($slot) use ($realMetrics, $forecastMetrics, $callMetrics) {
            $real = $realMetrics->get($slot['key']);
            $forecast = $forecastMetrics->get($slot['key']);
            $calls = $callMetrics->get($slot['key']);

            $forecastCalls = $forecast?->forecast_calls;
            $realCalls = $real?->calls ?? $calls?->total ?? 0;

            return (object) [
                'slot' => $slot['slot'],
                'label' => $slot['label'],
                'forecast' => $forecastCalls !== null ? (int) $forecastCalls : null,
                'real' => (int) $realCalls,
                'occupancy' => $real?->occupancy !== null ? round((float) $real->occupancy, 1) : null,
                'aht' => $real?->aht !== null ? round((float) $real->aht, 0) : ($forecast?->forecast_aht !== null ? round((float) $forecast->forecast_aht, 0) : null),
                'asa' => $calls?->avg_asa !== null ? round((float) $calls->avg_asa, 0) : null,
                'sl' => $calls && $calls->total > 0 ? ServiceQualityMetrics::serviceLevel((int) $calls->sl_count, (int) $calls->total) : null,
                'adherence' => $real?->adherence !== null ? round((float) $real->adherence, 1) : null,
                'calls_handled' => $real?->calls ?? 0,
            ];
        });

        return view('operations::livewire.interval-dashboard', [
            'rows' => $rows,
        ])->layout('layouts.app', ['title' => 'Intervalos - '.Carbon::parse($this->date)->format('d/m/Y')]);
    }

    private function buildSlots(): array
    {
        $slots = [];
        $intervalMinutes = 15;
        for ($slot = 0; $slot < 96; $slot++) {
            $start = sprintf('%02d:%02d', intdiv($slot * $intervalMinutes, 60), ($slot * $intervalMinutes) % 60);
            $end = sprintf('%02d:%02d', intdiv(($slot + 1) * $intervalMinutes, 60), (($slot + 1) * $intervalMinutes) % 60);
            $slots[] = [
                'slot' => $slot + 1,
                'key' => $start,
                'label' => "{$start} - {$end}",
            ];
        }

        return $slots;
    }

    private function getRealMetrics(): Collection
    {
        return AgentIntervalMetric::whereDate('interval_start', $this->date)
            ->select(
                DB::raw("to_char(interval_start, 'HH24:MI') as interval_key"),
                DB::raw('SUM(calls_handled) as calls'),
                DB::raw('AVG(aht_seconds) as aht'),
                DB::raw('AVG(occupancy) as occupancy'),
                DB::raw('AVG(adherence) as adherence'),
            )
            ->groupBy('interval_key')
            ->get()
            ->keyBy('interval_key');
    }

    private function getForecastMetrics(): Collection
    {
        $scenario = ForecastScenario::active()->first();
        if (! $scenario) {
            return collect();
        }

        return ForecastInterval::where('forecast_scenario_id', $scenario->id)
            ->whereDate('interval_start', $this->date)
            ->select(
                DB::raw("to_char(interval_start, 'HH24:MI') as interval_key"),
                DB::raw('SUM(call_volume_forecast) as forecast_calls'),
                DB::raw('AVG(aht_seconds_forecast) as forecast_aht'),
                DB::raw('SUM(staff_required) as staff_required'),
            )
            ->groupBy('interval_key')
            ->get()
            ->keyBy('interval_key');
    }

    private function getCallMetricsByInterval(): Collection
    {
        return DB::table('call_records')
            ->whereDate('ivr_started_at', $this->date)
            ->whereNotNull('queue_id')
            ->select(
                DB::raw("to_char(date_trunc('hour', ivr_started_at) + (floor(extract(minute from ivr_started_at) / 15)::int * interval '15 min'), 'HH24:MI') as interval_key"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
                DB::raw('AVG(CASE WHEN contact_disposition = 2 THEN queue_time ELSE NULL END) as avg_asa'),
                DB::raw('SUM(CASE WHEN contact_disposition = 2 AND queue_time <= 20 THEN 1 ELSE 0 END) as sl_count'),
            )
            ->groupBy('interval_key')
            ->get()
            ->keyBy('interval_key');
    }
}
