<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function render()
    {
        $date = Carbon::parse($this->selectedDate);
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])->pluck('id')->toArray();
        $distribution = [
            'Ready' => 0,
            'Talking' => 0,
            'AUX' => 0,
            'Offline' => 0,
        ];

        try {
            if (! $date->isToday()) {
                $states = DB::table('agent_state_transitions')
                    ->whereIn('employee_id', $operatorIds)
                    ->whereDate('transition_time', $this->selectedDate)
                    ->select('agent_state', DB::raw('count(distinct employee_id) as count'))
                    ->groupBy('agent_state')
                    ->get()
                    ->pluck('count', 'agent_state')
                    ->toArray();

                $distribution = [
                    'Ready' => $states['READY'] ?? 0,
                    'Talking' => $states['TALKING'] ?? 0,
                    'AUX' => ($states['NOT_READY'] ?? 0) + ($states['WORK'] ?? 0),
                    'Offline' => ($states['LOGOUT'] ?? 0) + ($states['OFFLINE'] ?? 0),
                ];
            } else {
                $states = AgentRealtimeState::whereIn('employee_id', $operatorIds)
                    ->select('current_state', DB::raw('count(*) as count'))
                    ->groupBy('current_state')
                    ->get()
                    ->pluck('count', 'current_state')
                    ->toArray();

                $distribution = [
                    'Ready' => $states['READY'] ?? 0,
                    'Talking' => $states['TALKING'] ?? 0,
                    'AUX' => ($states['NOT_READY'] ?? 0) + ($states['WORK'] ?? 0),
                    'Offline' => ($states['LOGOUT'] ?? 0) + ($states['OFFLINE'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            // Fallback silencioso en caso de error de CTI o base de datos
        }

        return view('operations::livewire.widgets.state-distribution-widget', [
            'stateDistribution' => $distribution,
        ]);
    }
}
