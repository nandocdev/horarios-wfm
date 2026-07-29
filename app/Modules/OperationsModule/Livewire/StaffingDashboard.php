<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Actions\CalculateStaffingRequirementsAction;
use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\AnalyticsModule\Models\StaffingRequirement;
use App\Shared\Support\CallQueueCache;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Url;
use Livewire\Component;

class StaffingDashboard extends Component
{
    #[Url]
    public string $date = '';

    #[Url]
    public ?string $queueFilter = null;

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
    }

    public function calculate(CalculateStaffingRequirementsAction $action): void
    {
        $scenario = ForecastScenario::active()->first();
        if (! $scenario) {
            $this->dispatch('notify', type: 'error', message: 'No hay un escenario de forecast activo. Genere un forecast primero.');

            return;
        }

        $action->execute(
            forecastScenarioId: $scenario->id,
            date: CarbonImmutable::parse($this->date),
            shrinkageRate: 0.0,
        );

        $this->dispatch('notify', message: 'Staffing calculado correctamente.');
    }

    public function render()
    {
        $scenario = ForecastScenario::active()->first();
        $forecastVersionId = $scenario?->forecast_version_id;

        $forecastIntervals = collect();
        $staffing = collect();
        $queues = app(CallQueueCache::class)->selectIds();

        if ($scenario && $forecastVersionId) {
            $forecastIntervals = ForecastInterval::where('forecast_scenario_id', $scenario->id)
                ->whereDate('interval_start', $this->date)
                ->orderBy('interval_start')
                ->get()
                ->keyBy(fn ($i) => $i->interval_start->format('H:i'));

            $staffingQuery = StaffingRequirement::whereDate('interval_start', $this->date);

            if ($this->queueFilter) {
                $staffingQuery->where('queue_id', $this->queueFilter);
            }

            $staffing = $staffingQuery->orderBy('interval_start')
                ->get()
                ->keyBy(fn ($s) => $s->interval_start->format('H:i'));
        }

        $slots = $this->buildSlots();

        $rows = collect($slots)->map(function ($slot) use ($forecastIntervals, $staffing) {
            $fi = $forecastIntervals->get($slot['key']);
            $sr = $staffing->get($slot['key']);

            return (object) [
                'slot' => $slot['slot'],
                'label' => $slot['label'],
                'forecast' => $fi?->call_volume_forecast,
                'required' => $sr?->required_agents,
                'scheduled' => $sr?->scheduled_agents,
                'available' => $sr?->available_agents,
                'gap' => $sr?->gap,
                'coverage' => $sr?->coverage,
            ];
        });

        $totalForecast = $forecastIntervals->sum('call_volume_forecast');
        $avgRequired = $staffing->avg('required_agents');
        $avgScheduled = $staffing->avg('scheduled_agents');
        $avgCoverage = $staffing->avg('coverage');

        return view('operations::livewire.staffing-dashboard', [
            'scenario' => $scenario,
            'rows' => $rows,
            'totalForecast' => $totalForecast,
            'avgRequired' => $avgRequired,
            'avgScheduled' => $avgScheduled,
            'avgCoverage' => $avgCoverage,
            'queues' => $queues,
        ])->layout('layouts.app', ['title' => 'Staffing - '.Carbon::parse($this->date)->format('d/m/Y')]);
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
}
