<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class VolumeComparisonWidget extends Component
{
    public function placeholder()
    {
        return <<<'HTML'
        <div class="h-[400px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl animate-pulse"></div>
        HTML;
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $now = now();
        $startOfCurrentWeek = $now->copy()->startOfWeek();
        $endOfCurrentWeek = $now->copy()->endOfWeek();

        $startOfPreviousWeek = $startOfCurrentWeek->copy()->subWeek();
        $endOfPreviousWeek = $startOfPreviousWeek->copy()->endOfWeek();

        $currentWeekData = $realtimeRepo->getCallVolumeByDateRange(
            $startOfCurrentWeek->toDateString(),
            $endOfCurrentWeek->toDateString(),
        );

        $previousWeekData = $realtimeRepo->getCallVolumeByDateRange(
            $startOfPreviousWeek->toDateString(),
            $endOfPreviousWeek->toDateString(),
        );

        $labels = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie'];
        $currHandled = [];
        $currAbandoned = [];
        $prevHandled = [];
        $prevAbandoned = [];

        for ($i = 0; $i < 5; $i++) {
            $currDate = $startOfCurrentWeek->copy()->addDays($i)->toDateString();
            $prevDate = $startOfPreviousWeek->copy()->addDays($i)->toDateString();

            $currDay = $currentWeekData->get($currDate);
            $prevDay = $previousWeekData->get($prevDate);

            $currHandled[] = $currDay ? (int) $currDay->handled : 0;
            $currAbandoned[] = $currDay ? (int) $currDay->abandoned : 0;
            $prevHandled[] = $prevDay ? (int) $prevDay->handled : 0;
            $prevAbandoned[] = $prevDay ? (int) $prevDay->abandoned : 0;
        }

        $volumeComparison = [
            'labels' => $labels,
            'curr_week_label' => $startOfCurrentWeek->format('d/M'),
            'prev_week_label' => $startOfPreviousWeek->format('d/M'),
            'current_handled' => $currHandled,
            'current_abandoned' => $currAbandoned,
            'previous_handled' => $prevHandled,
            'previous_abandoned' => $prevAbandoned,
        ];

        return view('operations::livewire.widgets.volume-comparison-widget', [
            'volumeComparison' => $volumeComparison,
        ]);
    }
}
