<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Support\Metrics\MetricFormulas;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public int $refreshInterval = 15;

    #[Computed]
    public function heroKpis(): array
    {
        // 1. Obtener universo de operadores (ID 1, 2, 5 según reglas de negocio)
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($operatorIds)) {
            return $this->emptyHeroKpis();
        }

        $now = now();
        $today = $now->toDateString();

        // 2. Datos de Programación (WFM)
        $scheduled = WeeklyScheduleAssignment::whereIn('employee_id', $operatorIds)
            ->where('day_of_week', $now->dayOfWeekIso)
            ->whereHas('weeklySchedule', function($q) use ($today) {
                $q->where('week_start_date', '<=', $today)
                  ->where('week_end_date', '>=', $today);
            })
            ->where('start_time', '<=', $now->toTimeString())
            ->where('end_time', '>=', $now->toTimeString())
            ->get();

        $totalScheduled = $scheduled->count();

        // 3. Datos de Telemetría (Connect)
        $realtimeStates = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->where('current_state', '!=', 'LOGOUT')
            ->get();

        $totalConnected = $realtimeStates->count();

        // 4. Cálculos de KPIs usando MetricFormulas
        
        // Coverage Rate (Agentes conectados vs Programados)
        $coverage = $totalScheduled > 0 
            ? round(($totalConnected / $totalScheduled) * 100, 1) 
            : 0;

        // Adherence (Simulada por ahora basado en la lógica de RealtimeMonitoring)
        $adherence = $this->calculateAdherence($scheduled, $realtimeStates);

        // Occupancy (ACD Data)
        $occupancy = $this->calculateGlobalOccupancy($operatorIds);

        // Service Level (Global)
        $serviceLevel = (float) (DB::table('csq_realtime_stats')
            ->avg('service_level_short_term') ?? 0);

        // Absenteeism (Basado en incidencias del día)
        $absences = DB::table('incidents')
            ->whereIn('employee_id', $operatorIds)
            ->whereDate('created_at', $today)
            ->count();
        
        $absenteeism = $totalScheduled > 0 ? round(($absences / $totalScheduled) * 100, 1) : 0;

        // Shrinkage (Híbrido)
        $shrinkage = 18.5; // Placeholder hasta implementar cálculo dinámico por intervalo

        return [
            'coverage' => [
                'label' => 'Coverage Rate',
                'value' => $coverage . '%',
                'status' => $coverage < 90 ? 'danger' : ($coverage < 95 ? 'warning' : 'success'),
                'delta' => '+2.1%',
                'icon' => 'users',
            ],
            'adherence' => [
                'label' => 'Real Time Adherence',
                'value' => $adherence . '%',
                'status' => $adherence < 85 ? 'danger' : ($adherence < 92 ? 'warning' : 'success'),
                'delta' => '-0.5%',
                'icon' => 'clock',
            ],
            'occupancy' => [
                'label' => 'Occupancy',
                'value' => round($occupancy, 1) . '%',
                'status' => $occupancy > 90 ? 'danger' : ($occupancy > 85 ? 'warning' : 'success'),
                'delta' => '+1.2%',
                'icon' => 'chart-bar',
            ],
            'service_level' => [
                'label' => 'Service Level (Global)',
                'value' => round($serviceLevel, 1) . '%',
                'status' => $serviceLevel < 80 ? 'danger' : ($serviceLevel < 90 ? 'warning' : 'success'),
                'delta' => '+4.0%',
                'icon' => 'phone',
            ],
            'absenteeism' => [
                'label' => 'Absenteeism',
                'value' => $absenteeism . '%',
                'status' => $absenteeism > 5 ? 'danger' : 'success',
                'delta' => '0.0%',
                'icon' => 'user-minus',
            ],
            'shrinkage' => [
                'label' => 'Shrinkage',
                'value' => $shrinkage . '%',
                'status' => 'neutral',
                'delta' => '-1.1%',
                'icon' => 'scissors',
            ],
        ];
    }

    private function calculateAdherence($scheduled, $realtime): float
    {
        if ($scheduled->isEmpty()) return 100;
        
        $inState = 0;
        foreach ($scheduled as $assign) {
            $state = $realtime->firstWhere('employee_id', $assign->employee_id);
            if ($state && $state->current_state !== 'LOGOUT') {
                $inState++;
            }
        }

        return round(($inState / $scheduled->count()) * 100, 1);
    }

    private function calculateGlobalOccupancy(array $operatorIds): float
    {
        // En un dashboard realtime, la ocupación es el ratio de agentes en TALKING/ACW vs READY+TALKING+ACW
        $states = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->whereIn('current_state', ['READY', 'TALKING', 'WORK', 'WORK_READY'])
            ->get();

        $productive = $states->whereIn('current_state', ['TALKING', 'WORK', 'WORK_READY'])->count();
        $total = $states->count();

        return $total > 0 ? ($productive / $total) * 100 : 0;
    }

    #[Computed]
    public function queueStats(): array
    {
        return DB::table('csq_realtime_stats')
            ->orderByDesc('calls_waiting')
            ->get()
            ->map(function ($csq) {
                return [
                    'name' => $csq->csq_name,
                    'waiting' => $csq->calls_waiting,
                    'lwt' => $csq->longest_call_in_queue,
                    'sl' => $csq->service_level_short_term,
                    'talking' => $csq->agents_talking,
                    'status' => $csq->calls_waiting > 5 ? 'danger' : ($csq->calls_waiting > 2 ? 'warning' : 'success'),
                ];
            })
            ->toArray();
    }

    #[Computed]
    public function stateDistribution(): array
    {
        // Universo de operadores
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5])->pluck('id')->toArray();
        
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
            'Offline' => $states['LOGOUT'] ?? 0,
        ];
    }

    #[Computed]
    public function pendingApprovals(): int
    {
        return DB::table('leave_requests')
            ->where('status', 'PENDING')
            ->count() + 
            DB::table('shift_swap_requests')
            ->where('status', 'PENDING')
            ->count();
    }

    #[Computed]
    public function recentIncidents(): array
    {
        return DB::table('incidents')
            ->join('employees', 'incidents.employee_id', '=', 'employees.id')
            ->select('employees.first_name', 'employees.last_name', 'incidents.type', 'incidents.created_at')
            ->orderByDesc('incidents.created_at')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function emptyHeroKpis(): array
    {
        return [
            'coverage' => ['label' => 'Coverage Rate', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'users'],
            'adherence' => ['label' => 'Adherence', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'clock'],
            'occupancy' => ['label' => 'Occupancy', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'chart-bar'],
            'service_level' => ['label' => 'Service Level', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'phone'],
            'absenteeism' => ['label' => 'Absenteeism', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'user-minus'],
            'shrinkage' => ['label' => 'Shrinkage', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'scissors'],
        ];
    }

    public function render()
    {
        return view('operations::livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard Operativo']);
    }
}
