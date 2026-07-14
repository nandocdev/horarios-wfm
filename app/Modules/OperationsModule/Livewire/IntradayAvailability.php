<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Actions\CalculateRealAdherenceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Componente interactivo para el Dashboard de Disponibilidad Intradía.
 * Muestra el estado actual de los agentes comparado con su horario planificado.
 */
class IntradayAvailability extends Component
{
    public int $refreshInterval = 10;

    public function __construct(
        private readonly TelemetryRealtimeRepositoryInterface $realtimeRepo,
        private readonly DashboardScheduleQueriesInterface $scheduleQueries,
    ) {
        parent::__construct();
    }

    #[Computed]
    public function realtimeMetrics(): array
    {
        $operadorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])
            ->pluck('id')
            ->toArray();

        if (empty($operadorIds)) {
            return [
                'total_scheduled' => 0,
                'total_connected' => 0,
                'adherence' => 0,
                'talking' => 0,
                'ready' => 0,
                'not_ready' => 0,
                'not_ready_breakdown' => [],
                'raw_states' => collect(),
            ];
        }

        $realtimeStates = $this->realtimeRepo->getRealtimeStates($operadorIds)
            ->where('current_state', '!=', 'LOGOUT');

        $now = now();
        $today = $now->toDateString();
        $time = $now->toTimeString();

        $currentAssignments = $this->scheduleQueries->getScheduledForTime($operadorIds, $today, $now->dayOfWeekIso, $time);

        $scheduledEmployeeIds = $currentAssignments->pluck('employee_id')->toArray();
        $connectedEmployeeIds = $realtimeStates->pluck('employee_id')->toArray();

        $activeExceptions = $this->scheduleQueries->getActiveExceptionIds($operadorIds, $now);

        $totalScheduled = count($scheduledEmployeeIds) - count($activeExceptions);
        $totalConnected = count($connectedEmployeeIds);

        $absentIds = array_diff($scheduledEmployeeIds, $connectedEmployeeIds, $activeExceptions);
        $totalAbsent = count($absentIds);

        $totalExceptions = count(array_intersect($scheduledEmployeeIds, $activeExceptions));

        $talking = $realtimeStates->where('current_state', 'TALKING')->count();
        $ready = $realtimeStates->where('current_state', 'READY')->count();
        $notReady = $realtimeStates->where('current_state', 'NOT_READY')->count();

        $adherenceRes = app(CalculateRealAdherenceAction::class)->executeBatch($operadorIds, $now);
        $adherence = $adherenceRes['percentage'];

        $notReadyBreakdown = $realtimeStates->where('current_state', 'NOT_READY')
            ->groupBy('reason_code')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $agentsTalkingByQueue = [];
        foreach ($realtimeStates->where('current_state', 'TALKING') as $state) {
            $meta = $state->metadata ?? [];
            $callInfo = $meta['call_info'] ?? null;
            $queueName = 'Directa / Outbound';

            if ($callInfo && isset($callInfo['queue_name']) && ! empty($callInfo['queue_name'])) {
                $q = $callInfo['queue_name'];
                $queueName = is_array($q) ? ($q[0] ?? 'Directa / Outbound') : $q;
            }

            $agentsTalkingByQueue[$queueName] = ($agentsTalkingByQueue[$queueName] ?? 0) + 1;
        }

        $csqSummary = $this->realtimeRepo->getCsqRealtimeStats()
            ->map(function ($csq) use (&$agentsTalkingByQueue) {
                $csq->agents_talking = $agentsTalkingByQueue[$csq->csq_name] ?? 0;
                unset($agentsTalkingByQueue[$csq->csq_name]);

                return $csq;
            });

        foreach ($agentsTalkingByQueue as $name => $count) {
            $csqSummary->push((object) [
                'csq_name' => $name,
                'calls_waiting' => 0,
                'longest_call_in_queue' => 0,
                'service_level_short_term' => 0,
                'agents_talking' => $count,
                'total_calls_since_midnight' => 0,
                'calls_handled_since_midnight' => 0,
                'calls_abandoned_since_midnight' => 0,
            ]);
        }

        return [
            'total_scheduled' => $totalScheduled,
            'total_connected' => $totalConnected,
            'total_absent' => $totalAbsent,
            'total_exceptions' => $totalExceptions,
            'adherence' => $adherence,
            'talking' => $talking,
            'ready' => $ready,
            'not_ready' => $notReady,
            'not_ready_breakdown' => $notReadyBreakdown,
            'csq_summary' => $csqSummary,
        ];
    }

    public function render()
    {
        return view('operations::livewire.intraday-availability')
            ->layout('layouts.app', ['title' => 'Disponibilidad Intradía']);
    }
}
