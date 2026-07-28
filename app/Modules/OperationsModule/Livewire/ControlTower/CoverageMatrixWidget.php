<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class CoverageMatrixWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-72 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render(
        DashboardScheduleQueriesInterface $scheduleQueries,
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $today = $this->selectedDate;
        $now = now();

        $coverageSlots = $scheduleQueries->getCoverageSlots($this->employeeIds, $today);

        $rows = $coverageSlots->map(function ($slot) use ($realtimeRepo) {
            $hour = (int) $slot['hour'];
            $scheduled = $slot['assigned'];
            $label = sprintf('%02d:00', $hour);

            $states = $realtimeRepo->getRealtimeStates([]);
            $available = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();
            $forecast = max(1, (int) round($scheduled * 1.1));
            $gap = $forecast - $available;
            $coverage = $forecast > 0 ? round(($available / $forecast) * 100, 1) : 0;

            $class = match (true) {
                $gap <= -5 => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400',
                $gap <= 0 => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400',
                $gap <= 3 => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400',
                default => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400',
            };

            return [
                'hour' => $label,
                'forecast' => $forecast,
                'scheduled' => $scheduled,
                'available' => $available,
                'gap' => $gap,
                'coverage' => $coverage,
                'class' => $class,
            ];
        });

        return view('operations::livewire.control-tower.coverage-matrix-widget', [
            'rows' => $rows,
        ]);
    }
}
