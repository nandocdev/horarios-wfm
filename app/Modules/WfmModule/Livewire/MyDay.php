<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Actions\Realtime\GetExpectedAgentStateAction;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class MyDay extends Component
{
    public $employee;
    public $stats = [];

    public function mount()
    {
        $user = Auth::user();
        $this->employee = $user->employee;

        if ($this->employee) {
            $this->loadStats();
        }
    }

    public function loadStats()
    {
        $employeeId = $this->employee->id;
        $date = now()->toDateString();

        $this->stats = Cache::remember("stats:{$employeeId}:{$date}", 300, function () use ($employeeId, $date) {
            $transitions = DB::table('agent_state_transitions')
                ->where('employee_id', $employeeId)
                ->whereDate('transition_time', $date)
                ->get();

            $totalSeconds = 0;
            $productiveSeconds = 0;
            $availableSeconds = 0;

            $productiveStates = ['READY', 'RESERVED', 'TALKING', 'WORK', 'HOLD', 'OUTBOUND'];

            foreach ($transitions as $t) {
                if ($t->duration) {
                    $totalSeconds += $t->duration;
                    $state = strtoupper(trim((string) $t->agent_state));
                    
                    if (in_array($state, ['READY', 'RESERVED', 'TALKING', 'WORK', 'HOLD', 'OUTBOUND'])) {
                        // Tiempo "Efectivo" para ocupación (excluye pausas/logouts)
                        if (in_array($state, ['RESERVED', 'TALKING', 'WORK', 'HOLD', 'OUTBOUND'])) {
                            $productiveSeconds += $t->duration;
                        }
                        if ($state === 'READY') {
                            $availableSeconds += $t->duration;
                        }
                    }
                }
            }

            $effectiveTime = $productiveSeconds + $availableSeconds;

            return [
                'total_time' => sprintf('%02dh %02dm', floor($totalSeconds / 3600), floor(($totalSeconds % 3600) / 60)),
                'productive_time' => sprintf('%02dh %02dm', floor($productiveSeconds / 3600), floor(($productiveSeconds % 3600) / 60)),
                'occupancy' => $effectiveTime > 0 ? round(($productiveSeconds / $effectiveTime) * 100) : 0,
            ];
        });
    }

    public function render()
    {
        \Illuminate\Support\Facades\Log::emergency("CRITICAL: Rendering MyDay for Employee ID: " . ($this->employee->id ?? 'NONE'));
        return view('wfm::livewire.my-day')->layout('layouts.app');
    }
}

