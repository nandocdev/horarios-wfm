<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\OperationsModule\Services\PerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component {
    public int $refreshInterval = 15;
    public string $selectedDate;

    public function mount(): void {
        $this->selectedDate = now()->toDateString();
    }

    #[Computed]
    public function isHistorical(): bool {
        return $this->selectedDate !== now()->toDateString();
    }

    #[Computed]
    public function heroKpis(): array {
        return app(PerformanceService::class)->getGlobalHeroKpis(Carbon::parse($this->selectedDate)) ?: $this->emptyHeroKpis();
    }

    #[Computed]
    public function queueStats(): array {
        $date = Carbon::parse($this->selectedDate);
        
        if (!$date->isToday()) {
            return DB::table('call_records')
                ->join('call_queues', 'call_records.queue_id', '=', 'call_queues.id')
                ->whereDate('ivr_started_at', $this->selectedDate)
                ->select(
                    'call_queues.name',
                    DB::raw('0 as waiting'),
                    DB::raw('0 as lwt'),
                    DB::raw('AVG(CASE WHEN contact_disposition = 2 THEN 100 ELSE 0 END) as sl'),
                    DB::raw('0 as talking'),
                    DB::raw('COUNT(*) as received'),
                    DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
                    DB::raw('SUM(CASE WHEN contact_disposition = 3 THEN 1 ELSE 0 END) as abandoned'),
                    DB::raw("'neutral' as status")
                )
                ->groupBy('call_queues.name')
                ->get()
                ->map(fn($item) => (array) $item)
                ->toArray();
        }

        // 1. Obtener conteo de agentes hablando por cola desde el universo de operadores
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])->pluck('id')->toArray();

        $talkingByQueue = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->where('current_state', 'TALKING')
            ->get()
            ->groupBy(function ($agent) {
                return $agent->metadata['call_info']['queue_name'] ?? 'OTROS';
            })
            ->map->count();

        $stats = DB::table('csq_realtime_stats')
            ->where('total_calls_since_midnight', '>', 0) // Solo colas con actividad
            ->orderByDesc('calls_waiting')
            ->get()
            ->map(function ($csq) use ($talkingByQueue) {
                return [
                    'name' => $csq->csq_name,
                    'waiting' => $csq->calls_waiting,
                    'lwt' => $csq->longest_call_in_queue,
                    'sl' => $csq->service_level_long_term,
                    'talking' => $talkingByQueue->get($csq->csq_name, 0),
                    'received' => $csq->total_calls_since_midnight,
                    'handled' => $csq->calls_handled_since_midnight,
                    'abandoned' => $csq->calls_abandoned_since_midnight,
                    'status' => $csq->calls_waiting > 5 ? 'danger' : ($csq->calls_waiting > 2 ? 'warning' : 'success'),
                ];
            })
            ->toArray();

        // 2. Si hay agentes hablando fuera de las colas monitoreadas (Salientes, Directos, etc), añadirlos
        $otherTalking = $talkingByQueue->get('OTROS', 0);
        if ($otherTalking > 0) {
            $stats[] = [
                'name' => 'LLAMADAS DIRECTAS / SALIENTES',
                'waiting' => 0,
                'lwt' => 0,
                'sl' => 100,
                'talking' => $otherTalking,
                'received' => 0,
                'handled' => 0,
                'abandoned' => 0,
                'status' => 'success',
            ];
        }

        return $stats;
    }

    #[Computed]
    public function stateDistribution(): array {
        $date = Carbon::parse($this->selectedDate);
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])->pluck('id')->toArray();

        if (!$date->isToday()) {
            $states = DB::table('agent_state_transitions')
                ->whereIn('employee_id', $operatorIds)
                ->whereDate('transition_time', $this->selectedDate)
                ->select('agent_state', DB::raw('count(distinct employee_id) as count'))
                ->groupBy('agent_state')
                ->get()
                ->pluck('count', 'agent_state')
                ->toArray();

            return [
                'Ready' => $states['READY'] ?? 0,
                'Talking' => $states['TALKING'] ?? 0,
                'AUX' => ($states['NOT_READY'] ?? 0) + ($states['WORK'] ?? 0),
                'Offline' => ($states['LOGOUT'] ?? 0) + ($states['OFFLINE'] ?? 0),
            ];
        }

        $states = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->select('current_state', DB::raw('count(*) as count'))
            ->groupBy('current_state')
            ->get()
            ->pluck('count', 'current_state')
            ->toArray();

        return [
            'Ready' => $states['READY'] ?? 0,
            'Talking' => $states['TALKING'] ?? 0,
            'AUX' => ($states['NOT_READY'] ?? 0) + ($states['WORK'] ?? 0),
            'Offline' => ($states['LOGOUT'] ?? 0) + ($states['OFFLINE'] ?? 0),
        ];
    }

    #[Computed]
    public function pendingApprovals(): int {
        return DB::table('leave_requests')
            ->where('status', 'pending')
            ->count() +
            DB::table('shift_swap_requests')
                ->where('status', 'pending')
                ->count();
    }

    #[Computed]
    public function recentIncidents(): array {
        return AttendanceIncident::with(['employee', 'type'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($incident) => (object) [
                'first_name' => $incident->employee->first_name,
                'last_name' => $incident->employee->last_name,
                'type' => $incident->type->name,
                'created_at' => $incident->created_at->toDateTimeString(),
            ])
            ->toArray();
    }

    private function emptyHeroKpis(): array {
        return [
            'coverage' => ['label' => 'Cobertura', 'value' => '0%', 'status' => 'neutral', 'icon' => 'users'],
            'adherence' => ['label' => 'Adherencia', 'value' => '0%', 'status' => 'neutral', 'icon' => 'clock'],
            'occupancy' => ['label' => 'Ocupación', 'value' => '0%', 'status' => 'neutral', 'icon' => 'chart-bar'],
            'service_level' => ['label' => 'Nivel de Servicio', 'value' => '0%', 'status' => 'neutral', 'icon' => 'phone'],
            'absenteeism' => ['label' => 'Ausentismo', 'value' => '0%', 'status' => 'neutral', 'icon' => 'user-minus'],
            'shrinkage' => ['label' => 'Shrinkage', 'value' => '0%', 'status' => 'neutral', 'icon' => 'scissors'],
        ];
    }

    public function render() {
        return view('operations::livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard Operativo']);
    }
}
