<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\CsqRealtimeStat;
use App\Modules\OperationsModule\Actions\CalculateRealAdherenceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Componente interactivo para el Dashboard de Disponibilidad Intradía.
 * Muestra el estado actual de los agentes comparado con su horario planificado.
 */
class IntradayAvailability extends Component
{
    /**
     * Define el intervalo de actualización automática (polling).
     */
    public int $refreshInterval = 10; // Segundos

    #[Computed]
    public function realtimeMetrics(): array
    {
        // 0. Obtener los IDs de los empleados que son posiciones operativas (IDs 1, 2, 5, 11, 13)
        $operadorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])
            ->pluck('id')
            ->toArray();

        if (empty($operadorIds)) {
            // Early return si no hay operadores
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

        // 1. Obtener todos los estados de Finesse (agentes conectados)
        $realtimeStates = AgentRealtimeState::select('employee_id', 'external_id', 'current_state', 'reason_code', 'metadata')
            ->where('current_state', '!=', 'LOGOUT')
            ->whereIn('employee_id', $operadorIds)
            ->get();

        // 2. Obtener horarios del día actual
        $now = now();
        $currentAssignments = WeeklyScheduleAssignment::whereHas('weeklySchedule', function ($q) use ($now) {
            $q->where('week_start_date', '<=', $now->toDateString())
                ->where('week_end_date', '>=', $now->toDateString());
        })
            ->whereIn('employee_id', $operadorIds)
            ->where('day_of_week', $now->dayOfWeekIso)
            ->where('start_time', '<=', $now->toTimeString())
            ->where('end_time', '>=', $now->toTimeString())
            ->get();

        $scheduledEmployeeIds = $currentAssignments->pluck('employee_id')->toArray();
        $connectedEmployeeIds = $realtimeStates->pluck('employee_id')->toArray();

        // 3. Obtener excepciones activas
        $activeExceptions = ScheduleException::whereIn('employee_id', $scheduledEmployeeIds)
            ->where(function ($q) use ($now) {
                $q->where(function ($q2) use ($now) {
                    $q2->where('start_at', '<=', $now)
                        ->where('end_at', '>=', $now);
                })->orWhere(function ($q2) use ($now) {
                    $q2->where('is_full_day', true)
                        ->whereDate('start_at', '<=', $now->toDateString())
                        ->whereDate('end_at', '>=', $now->toDateString());
                });
            })
            ->pluck('employee_id')
            ->toArray();

        // Cálculos
        $totalScheduled = count($scheduledEmployeeIds) - count($activeExceptions);
        $totalConnected = count($connectedEmployeeIds);

        // Ausentes (Agendados - Conectados - Excepcionados)
        $absentIds = array_diff($scheduledEmployeeIds, $connectedEmployeeIds, $activeExceptions);
        $totalAbsent = count($absentIds);

        // Agentes agendados que están en excepción
        $totalExceptions = count(array_intersect($scheduledEmployeeIds, $activeExceptions));

        $talking = $realtimeStates->where('current_state', 'TALKING')->count();
        $ready = $realtimeStates->where('current_state', 'READY')->count();
        $notReady = $realtimeStates->where('current_state', 'NOT_READY')->count();

        // Adherencia Real Intradía (Desde el inicio hasta ahora)
        $adherenceRes = app(CalculateRealAdherenceAction::class)->executeBatch($operadorIds, $now);
        $adherence = $adherenceRes['percentage'];

        // Breakdown de Not Ready
        $notReadyBreakdown = $realtimeStates->where('current_state', 'NOT_READY')
            ->groupBy('reason_code')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Agentes hablando por cola
        $agentsTalkingByQueue = [];
        foreach ($realtimeStates->where('current_state', 'TALKING') as $state) {
            $meta = json_decode($state->metadata, true) ?? [];
            $callInfo = $meta['call_info'] ?? null;
            $queueName = 'Directa / Outbound';

            if ($callInfo && isset($callInfo['queue_name']) && ! empty($callInfo['queue_name'])) {
                $q = $callInfo['queue_name'];
                $queueName = is_array($q) ? ($q[0] ?? 'Directa / Outbound') : $q;
            }

            $agentsTalkingByQueue[$queueName] = ($agentsTalkingByQueue[$queueName] ?? 0) + 1;
        }

        $csqSummary = CsqRealtimeStat::orderByDesc('calls_waiting')
            ->get()
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
