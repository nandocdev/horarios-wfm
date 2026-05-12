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

            foreach ($transitions as $t) {
                if ($t->duration) {
                    $totalSeconds += $t->duration;
                    
                    $nonProductiveStates = ['NOT READY', 'LOGOUT', 'NOT_READY'];
                    if (!in_array(strtoupper((string) $t->agent_state), $nonProductiveStates)) {
                        $productiveSeconds += $t->duration;
                    }
                }
            }

            return [
                'total_time' => sprintf('%02dh %02dm', floor($totalSeconds / 3600), floor(($totalSeconds % 3600) / 60)),
                'productive_time' => sprintf('%02dh %02dm', floor($productiveSeconds / 3600), floor(($productiveSeconds % 3600) / 60)),
                'occupancy' => $totalSeconds > 0 ? round(($productiveSeconds / $totalSeconds) * 100) : 0,
            ];
        });
    }

    public function render()
    {
        \Illuminate\Support\Facades\Log::emergency("CRITICAL: Rendering MyDay for Employee ID: " . ($this->employee->id ?? 'NONE'));
        return view('wfm::livewire.my-day')->layout('layouts.app');
    }
}

