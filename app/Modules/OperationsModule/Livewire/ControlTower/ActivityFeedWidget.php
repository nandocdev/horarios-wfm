<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ActivityFeedWidget extends Component
{
    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function render()
    {
        $today = $this->selectedDate;
        $activities = collect();

        $recentLeaves = LeaveRequest::with('employee')
            ->whereDate('created_at', '>=', Carbon::parse($today)->subDay())
            ->latest()
            ->limit(3)
            ->get();

        foreach ($recentLeaves as $leave) {
            $name = $leave->employee?->full_name ?? 'Empleado';
            $type = $leave->type === 'vacation' ? 'Vacaciones' : 'Permiso';
            $activities->push([
                'time' => $leave->created_at->format('H:i'),
                'text' => "{$name} solicitó {$type}",
            ]);
        }

        $recentSwaps = ShiftSwapRequest::with('requester')
            ->whereDate('created_at', '>=', Carbon::parse($today)->subDay())
            ->latest()
            ->limit(3)
            ->get();

        foreach ($recentSwaps as $swap) {
            $name = $swap->requester?->full_name ?? 'Empleado';
            $activities->push([
                'time' => $swap->created_at->format('H:i'),
                'text' => "{$name} solicitó cambio de turno",
            ]);
        }

        $recentPublications = WeeklySchedule::where('status', 'published')
            ->whereDate('published_at', '>=', Carbon::parse($today)->subDay())
            ->latest()
            ->limit(2)
            ->get();

        foreach ($recentPublications as $ws) {
            $activities->push([
                'time' => $ws->published_at?->format('H:i') ?? '--:--',
                'text' => 'Semana del '.$ws->week_start_date->format('d/m').' publicada',
            ]);
        }

        return view('operations::livewire.control-tower.activity-feed-widget', [
            'activities' => $activities->sortByDesc('time')->take(6),
        ]);
    }
}
