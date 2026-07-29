<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Actions\GenerateForecastAction;
use App\Modules\AnalyticsModule\Models\ForecastGroup;
use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastVersion;
use App\Modules\ConnectModule\Models\CallQueue;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;

class ForecastManager extends Component
{
    use WithPagination;

    public string $view = 'list';

    public ?string $selectedVersionId = null;

    public ?string $selectedScenarioId = null;

    public string $groupType = 'queue';

    public string $referenceId = '';

    public string $groupName = '';

    public string $startDate = '';

    public string $endDate = '';

    public int $historicalWeeks = 4;

    protected function queryString(): array
    {
        return [
            'view' => ['as' => 'v'],
            'selectedVersionId' => ['as' => 'version'],
            'selectedScenarioId' => ['as' => 'scenario'],
        ];
    }

    public function selectVersion(string $id): void
    {
        $this->selectedVersionId = $id;
        $this->selectedScenarioId = null;
        $this->view = 'detail';
    }

    public function selectScenario(string $id): void
    {
        $this->selectedScenarioId = $id;
    }

    public function back(): void
    {
        $this->view = 'list';
        $this->selectedVersionId = null;
        $this->selectedScenarioId = null;
    }

    public function showGenerate(): void
    {
        $startQueue = CallQueue::active()->orderBy('name')->first();
        if ($startQueue) {
            $this->referenceId = (string) $startQueue->id;
            $this->groupName = 'Forecast '.$startQueue->name;
        }
        $this->startDate = now()->toDateString();
        $this->endDate = now()->addWeeks(4)->toDateString();
        $this->view = 'generate';
    }

    public function generate(GenerateForecastAction $action): void
    {
        $this->validate([
            'groupName' => ['required', 'string', 'max:255'],
            'groupType' => ['required', 'in:queue'],
            'referenceId' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'historicalWeeks' => ['required', 'integer', 'min:1', 'max:52'],
        ]);

        $action->execute(
            groupName: $this->groupName,
            groupType: $this->groupType,
            referenceId: $this->referenceId,
            startDate: CarbonImmutable::parse($this->startDate),
            endDate: CarbonImmutable::parse($this->endDate),
            intervalMinutes: 15,
            historicalWeeks: $this->historicalWeeks,
            userId: (int) auth()->id(),
        );

        $this->reset(['groupName', 'referenceId', 'startDate', 'endDate', 'groupType', 'historicalWeeks']);
        $this->view = 'list';

        $this->dispatch('notify', message: 'Forecast generado correctamente.');
    }

    public function publishVersion(string $id): void
    {
        $version = ForecastVersion::findOrFail($id);
        $version->update(['status' => 'published']);
    }

    public function draftVersion(string $id): void
    {
        $version = ForecastVersion::findOrFail($id);
        $version->update(['status' => 'draft']);
    }

    public function render()
    {
        if ($this->view === 'detail' && $this->selectedVersionId) {
            return $this->renderDetail();
        }

        if ($this->view === 'generate') {
            return $this->renderGenerate();
        }

        return $this->renderList();
    }

    private function renderList()
    {
        $groups = ForecastGroup::with(['versions' => function ($q) {
            $q->latest()->with(['generator', 'scenarios']);
        }])
            ->orderBy('name')
            ->paginate(10);

        return view('operations::livewire.forecast-manager', [
            'view' => 'list',
            'groups' => $groups,
            'queues' => CallQueue::active()->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app', ['title' => 'Forecast']);
    }

    private function renderGenerate()
    {
        return view('operations::livewire.forecast-manager', [
            'view' => 'generate',
            'queues' => CallQueue::active()->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app', ['title' => 'Generar Forecast']);
    }

    private function renderDetail()
    {
        $version = ForecastVersion::with(['group', 'generator', 'scenarios'])->findOrFail($this->selectedVersionId);
        $scenario = $this->selectedScenarioId
            ? $version->scenarios->firstWhere('id', $this->selectedScenarioId)
            : $version->scenarios->first();

        $intervals = collect();
        if ($scenario) {
            $intervals = ForecastInterval::where('forecast_scenario_id', $scenario->id)
                ->orderBy('interval_start')
                ->get();
        }

        return view('operations::livewire.forecast-manager', [
            'view' => 'detail',
            'version' => $version,
            'scenario' => $scenario,
            'intervals' => $intervals,
        ])->layout('layouts.app', ['title' => 'Forecast: '.$version->name]);
    }
}
