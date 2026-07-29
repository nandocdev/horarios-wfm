<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Actions\GenerateCapacityPlanAction;
use App\Modules\AnalyticsModule\Models\CapacityInterval;
use App\Modules\AnalyticsModule\Models\CapacityPlan;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;

class CapacityDashboard extends Component
{
    use WithPagination;

    public string $view = 'list';

    public ?string $selectedPlanId = null;

    public string $planDate = '';

    public float $shrinkageRate = 0;

    protected function queryString(): array
    {
        return [
            'view' => ['as' => 'v'],
            'selectedPlanId' => ['as' => 'plan'],
        ];
    }

    public function selectPlan(string $id): void
    {
        $this->selectedPlanId = $id;
        $this->view = 'detail';
    }

    public function back(): void
    {
        $this->view = 'list';
        $this->selectedPlanId = null;
    }

    public function showGenerate(): void
    {
        $this->planDate = now()->toDateString();
        $this->shrinkageRate = 0;
        $this->view = 'generate';
    }

    public function generate(GenerateCapacityPlanAction $action): void
    {
        $this->validate([
            'planDate' => ['required', 'date'],
            'shrinkageRate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $scenario = ForecastScenario::active()->first();
        if (! $scenario) {
            $this->dispatch('notify', type: 'error', message: 'No hay un escenario de forecast activo.');

            return;
        }

        $action->execute(
            forecastScenarioId: $scenario->id,
            planDate: CarbonImmutable::parse($this->planDate),
            shrinkageRate: $this->shrinkageRate,
            generatedBy: (int) auth()->id(),
        );

        $this->reset(['planDate', 'shrinkageRate']);
        $this->view = 'list';

        $this->dispatch('notify', message: 'Plan de capacidad generado correctamente.');
    }

    public function publishPlan(string $id): void
    {
        CapacityPlan::findOrFail($id)->update(['status' => 'published']);
    }

    public function draftPlan(string $id): void
    {
        CapacityPlan::findOrFail($id)->update(['status' => 'draft']);
    }

    public function render()
    {
        if ($this->view === 'generate') {
            return $this->renderGenerate();
        }

        if ($this->view === 'detail' && $this->selectedPlanId) {
            return $this->renderDetail();
        }

        return $this->renderList();
    }

    private function renderList()
    {
        $plans = CapacityPlan::with(['generator', 'results'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('operations::livewire.capacity-dashboard', [
            'view' => 'list',
            'plans' => $plans,
        ])->layout('layouts.app', ['title' => 'Capacity Planning']);
    }

    private function renderGenerate()
    {
        return view('operations::livewire.capacity-dashboard', [
            'view' => 'generate',
        ])->layout('layouts.app', ['title' => 'Generar Plan de Capacidad']);
    }

    private function renderDetail()
    {
        $plan = CapacityPlan::with(['generator', 'results'])->findOrFail($this->selectedPlanId);
        $results = $plan->results;
        $intervals = CapacityInterval::where('capacity_plan_id', $plan->id)
            ->orderBy('interval_start')
            ->get();

        $slots = $this->buildSlots();
        $rows = collect($slots)->map(function ($slot) use ($intervals) {
            $ci = $intervals->first(fn ($i) => $i->interval_start->format('H:i') === $slot['key']);

            return (object) [
                'slot' => $slot['slot'],
                'label' => $slot['label'],
                'forecast' => $ci?->forecast_call_volume,
                'aht' => $ci?->forecast_aht,
                'required' => $ci?->staff_required,
                'scheduled' => $ci?->staff_scheduled,
                'available' => $ci?->staff_available,
                'with_skill' => $ci?->staff_with_skill,
                'coverage' => $ci?->coverage,
                'gap' => $ci?->gap,
                'skill_gap' => $ci?->skill_gap,
            ];
        });

        return view('operations::livewire.capacity-dashboard', [
            'view' => 'detail',
            'plan' => $plan,
            'results' => $results,
            'rows' => $rows,
            'intervals' => $intervals,
        ])->layout('layouts.app', ['title' => "Capacity: {$plan->name}"]);
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
