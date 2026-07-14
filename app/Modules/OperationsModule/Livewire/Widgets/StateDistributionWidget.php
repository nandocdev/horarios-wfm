<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class StateDistributionWidget extends Component
{
    public string $selectedDate;

    #[Computed]
    public function isHistorical(): bool
    {
        return $this->selectedDate !== now()->toDateString();
    }

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="h-[400px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl animate-pulse"></div>
        HTML;
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $date = Carbon::parse($this->selectedDate);
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])->pluck('id')->toArray();
        $distribution = [
            'Ready' => 0,
            'Talking' => 0,
            'AUX' => 0,
            'Offline' => 0,
        ];

        try {
            $distribution = ! $date->isToday()
                ? $realtimeRepo->getHistoricalStateDistribution($operatorIds, $this->selectedDate)
                : $realtimeRepo->getCurrentStateDistribution($operatorIds);
        } catch (\Exception $e) {
        }

        return view('operations::livewire.widgets.state-distribution-widget', [
            'stateDistribution' => $distribution,
        ]);
    }
}
