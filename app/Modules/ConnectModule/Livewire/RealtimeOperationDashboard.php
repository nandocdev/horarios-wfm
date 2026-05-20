<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Componente interactivo para el Dashboard de Operación en Tiempo Real.
 * Muestra el estado actual de los agentes comparado con su horario planificado.
 */
class RealtimeOperationDashboard extends Component
{
    /**
     * Define el intervalo de actualización automática (polling).
     */
    public int $refreshInterval = 10; // Segundos

    #[Computed]
    public function realtimeMetrics(): array
    {
        // 0. Obtener los IDs de los empleados que son "Operador I"
        $operadorIds = Employee::whereHas('position', function ($q) {
            $q->where('id', 1);
        })->pluck('id')->toArray();

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

        // 1. Obtener todos los estados de Finesse (agentes conectados) filtrados por Operador I
        $realtimeStates = DB::table('agent_realtime_states')
            ->select('employee_id', 'external_id', 'current_state', 'reason_code', 'metadata')
            ->where('current_state', '!=', 'LOGOUT')
            ->whereIn('employee_id', $operadorIds)
            ->get();

        // 2. Obtener horarios del día actual (los que DEBERÍAN estar trabajando ahora) filtrados por Operador I
        $now = now();
        $currentAssignments = WeeklyScheduleAssignment::whereHas('weeklySchedule', function ($q) use ($now) {
            $q->where('week_start_date', '<=', $now->toDateString())
                ->where('week_end_date', '>=', $now->toDateString());
        })
            ->whereIn('employee_id', $operadorIds)
            ->where('day_of_week', (clone $now)->setTimezone(config('app.timezone'))->dayOfWeekIso)
            ->where('start_time', '<=', $now->toTimeString())
            ->where('end_time', '>=', $now->toTimeString())
            ->get();

        $scheduledEmployeeIds = $currentAssignments->pluck('employee_id')->toArray();
        $connectedEmployeeIds = $realtimeStates->pluck('employee_id')->toArray();

        // 3. Obtener excepciones activas para los agentes agendados
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

        // Agentes agendados que están en excepción (vacaciones, licencias)
        $totalExceptions = count(array_intersect($scheduledEmployeeIds, $activeExceptions));

        $talking = $realtimeStates->where('current_state', 'TALKING')->count();
        $ready = $realtimeStates->where('current_state', 'READY')->count();
        $notReady = $realtimeStates->where('current_state', 'NOT_READY')->count();

        // Simulación de Adherencia (agentes conectados / (agentes agendados - excepciones))
        $netScheduled = max(0, $totalScheduled - $totalExceptions);
        $adherence = $netScheduled > 0 ? round((min($totalConnected, $netScheduled) / $netScheduled) * 100, 1) : 100;

        // Breakdown de Not Ready
        $notReadyBreakdown = $realtimeStates->where('current_state', 'NOT_READY')
            ->groupBy('reason_code')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Calcular agentes hablando por cola desde el Real Time de Operadores
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

        $csqSummary = DB::table('csq_realtime_stats')
            ->orderByDesc('calls_waiting')
            ->get()
            ->map(function ($csq) use (&$agentsTalkingByQueue) {
                // Sobrescribir agents_talking con la cuenta real de operadores
                $csq->agents_talking = $agentsTalkingByQueue[$csq->csq_name] ?? 0;
                // Eliminar del mapa para identificar las que no están en la tabla
                unset($agentsTalkingByQueue[$csq->csq_name]);

                return $csq;
            });

        // Agregar colas que no están en la tabla de stats (ej: Directa / Outbound)
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

        // Filtrar colas sin actividad (sin llamadas ofrecidas en el día y sin actividad actual)
        $csqSummary = $csqSummary->filter(function ($csq) {
            return $csq->total_calls_since_midnight > 0
                || $csq->agents_talking > 0
                || $csq->calls_waiting > 0;
        })->values();

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
        // Layout principal
        return view('connect::livewire.realtime-operation-dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard de Operación']);
    }
}
