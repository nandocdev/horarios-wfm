<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
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

    public function render(TelemetryRealtimeRepositoryInterface $realtimeRepo)
    {
        $today = $this->selectedDate;
        $dayOfWeek = Carbon::parse($today)->dayOfWeekIso;

        $assignments = WeeklyScheduleAssignment::whereHas('weeklySchedule', function ($q) use ($today) {
            $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today);
        })
            ->where('day_of_week', $dayOfWeek)
            ->where('is_replaced', false)
            ->whereIn('employee_id', $this->employeeIds)
            ->get(['employee_id', 'start_time', 'end_time']);

        $currentStates = $realtimeRepo->getRealtimeStates($this->employeeIds);
        $connectedNow = $currentStates->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();

        $rows = collect();
        for ($h = 7; $h <= 21; $h++) {
            $label = sprintf('%02d:00', $h);

            $scheduled = $assignments->filter(function ($a) use ($label) {
                if ($a->start_time === null || $a->end_time === null) {
                    return false;
                }

                return $a->start_time->format('H:i') <= $label
                    && $a->end_time->format('H:i') >= $label;
            })->count();

            $required = max(1, (int) round($scheduled * 1.1));
            $gap = $required - $connectedNow;

            $class = $gap <= -3 ? 'bg-green-50 dark:bg-green-900/20'
                : ($gap <= 0 ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20');

            $rows->push([
                'hour' => $label,
                'req' => $required,
                'prog' => $scheduled,
                'gap' => $gap,
                'class' => $class,
            ]);
        }

        return view('operations::livewire.control-tower.coverage-matrix-widget', [
            'rows' => $rows,
        ]);
    }
}
