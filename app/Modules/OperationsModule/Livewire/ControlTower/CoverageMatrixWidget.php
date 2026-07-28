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
        return '<div class="h-64 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render(
        DashboardScheduleQueriesInterface $scheduleQueries,
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $today = $this->selectedDate;

        $coverageSlots = $scheduleQueries->getCoverageSlots($this->employeeIds, $today);
        $currentStates = $realtimeRepo->getRealtimeStates($this->employeeIds);
        $availableNow = $currentStates->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();

        $rows = $coverageSlots->map(function ($slot) use ($availableNow) {
            $hour = (int) $slot['hour'];
            $scheduled = $slot['assigned'];
            $label = sprintf('%02d:00', $hour);

            $forecast = max(1, (int) round($scheduled * 1.1));
            $gap = $forecast - $availableNow;

            $signal = $gap <= -3 ? '🟢' : ($gap <= 0 ? '🟡' : '🔴');
            $class = $gap <= -3 ? 'bg-green-50 dark:bg-green-900/20' : ($gap <= 0 ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20');

            return ['hour' => $label, 'req' => $forecast, 'prog' => $scheduled, 'gap' => $gap, 'signal' => $signal, 'class' => $class];
        });

        return view('operations::livewire.control-tower.coverage-matrix-widget', [
            'rows' => $rows,
        ]);
    }
}
