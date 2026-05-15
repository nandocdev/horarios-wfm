<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Services;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Support\Metrics\MetricFormulas;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class PerformanceService
{
    /**
     * Calcula el Shrinkage (Reductores) dinámico para un grupo de empleados en una fecha.
     */
    public function calculateShrinkage(array $employeeIds, CarbonInterface $date): float
    {
        // Aseguramos instancia de Carbon para operaciones de mutación/clonación controlada
        $carbonDate = Carbon::instance($date);
        $startOfDay = $carbonDate->copy()->startOfDay();
        $endOfDay = $carbonDate->copy()->endOfDay();

        // 1. Minutos por Excepciones (Permisos, Vacaciones, Incapacidades)
        $exceptionMinutes = ScheduleException::whereIn('employee_id', $employeeIds)
            ->where('start_at', '<=', $endOfDay)
            ->where('end_at', '>=', $startOfDay)
            ->get()
            ->sum(function ($ex) use ($startOfDay, $endOfDay) {
                // Carbon ya maneja comparaciones con interfaces
                $start = $ex->start_at->max($startOfDay);
                $end = $ex->end_at->min($endOfDay);
                return max(0, $start->diffInMinutes($end));
            });

        // 2. Minutos por Actividades Intradía (Reuniones, Coaching, etc.)
        $intradayMinutes = IntradayActivity::whereIn('employee_id', $employeeIds)
            ->whereRaw('time_range && tstzrange(?, ?)', [$startOfDay->toIso8601String(), $endOfDay->toIso8601String()])
            ->get()
            ->sum(function ($activity) use ($startOfDay, $endOfDay) {
                $start = $activity->getRangeStart()?->max($startOfDay);
                $end = $activity->getRangeEnd()?->min($endOfDay);
                return $start && $end ? max(0, $start->diffInMinutes($end)) : 0;
            });

        // 3. Minutos Totales Programados (Jornada Bruta)
        $totalScheduledMinutes = WeeklyScheduleAssignment::whereIn('employee_id', $employeeIds)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->whereHas('weeklySchedule', function ($q) use ($date) {
                $q->where('week_start_date', '<=', $date->toDateString())
                  ->where('week_end_date', '>=', $date->toDateString());
            })
            ->with('schedule')
            ->get()
            ->sum(function ($assignment) {
                return $assignment->schedule?->total_minutes ?? 0;
            });

        if ($totalScheduledMinutes <= 0) {
            return 0.0;
        }

        $totalShrinkageMinutes = $exceptionMinutes + $intradayMinutes;

        return round(($totalShrinkageMinutes / $totalScheduledMinutes) * 100, 1);
    }

    /**
     * Calcula los KPIs globales para el Dashboard.
     */
    public function getGlobalHeroKpis(?CarbonInterface $targetDate = null): array
    {
        $date = $targetDate ?? now();
        $isToday = $date->isToday();
        $formattedDate = $date->toDateString();

        // 1. Universo de operadores
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($operatorIds)) {
            return [];
        }

        if (!$isToday) {
            // Lógica Histórica simplificada
            $callStats = DB::table('call_records')
                ->whereNotNull('queue_id')
                ->whereDate('ivr_started_at', $formattedDate)
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
                    DB::raw('AVG(talk_time) as avg_talk')
                )
                ->first();

            $serviceLevel = $callStats->total > 0 ? ($callStats->handled / $callStats->total) * 100 : 0;
            
            return [
                'coverage' => ['label' => 'Cobertura', 'value' => '100%', 'status' => 'success', 'delta' => '0.0%', 'icon' => 'users'],
                'adherence' => ['label' => 'Adherencia', 'value' => '95%', 'status' => 'success', 'delta' => '0.0%', 'icon' => 'clock'],
                'occupancy' => ['label' => 'Ocupación', 'value' => '85%', 'status' => 'success', 'delta' => '0.0%', 'icon' => 'chart-bar'],
                'service_level' => [
                    'label' => 'Nivel de Servicio',
                    'value' => round($serviceLevel, 1) . '%',
                    'status' => $serviceLevel < 80 ? 'danger' : 'success',
                    'delta' => '0.0%',
                    'icon' => 'phone',
                ],
                'absenteeism' => ['label' => 'Ausentismo', 'value' => '0%', 'status' => 'success', 'delta' => '0.0%', 'icon' => 'user-minus'],
                'shrinkage' => ['label' => 'Reductores', 'value' => '15%', 'status' => 'neutral', 'delta' => '0.0%', 'icon' => 'scissors'],
            ];
        }

        // Lógica Realtime (Original)
        $now = now();
        $today = $now->toDateString();

        // 1. Universo de operadores
        $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($operatorIds)) {
            return [];
        }

        // 2. Programados actualmente (Excluyendo excepciones activas)
        $idsWithExceptions = ScheduleException::whereIn('employee_id', $operatorIds)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->pluck('employee_id')
            ->toArray();

        $scheduled = WeeklyScheduleAssignment::whereIn('employee_id', $operatorIds)
            ->whereNotIn('employee_id', $idsWithExceptions)
            ->where('day_of_week', $now->dayOfWeekIso)
            ->whereHas('weeklySchedule', function ($q) use ($today) {
                $q->where('week_start_date', '<=', $today)
                  ->where('week_end_date', '>=', $today);
            })
            ->where('start_time', '<=', $now->toTimeString())
            ->where('end_time', '>=', $now->toTimeString())
            ->get();

        $totalScheduled = $scheduled->count();

        // 3. Conectados actualmente (Cisco)
        $realtimeStates = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])
            ->get();

        $totalConnected = $realtimeStates->count();

        // 4. Cálculos
        $coverage = $totalScheduled > 0 ? min(100, round(($totalConnected / $totalScheduled) * 100, 1)) : 0;
        $adherence = $this->calculateAdherence($scheduled, $realtimeStates);
        $occupancy = $this->calculateOccupancy($operatorIds);
        $serviceLevel = (float) (DB::table('csq_realtime_stats')->avg('service_level_long_term') ?? 0);
        
        $scheduledIds = $scheduled->pluck('employee_id')->toArray();
        $connectedFromScheduled = $realtimeStates->whereIn('employee_id', $scheduledIds)->count();
        $absenteeism = MetricFormulas::absenteeismRate(
            (float) MetricFormulas::absentPersonnel($totalScheduled, $connectedFromScheduled),
            (float) $totalScheduled
        );

        $shrinkage = $this->calculateShrinkage($operatorIds, $now);

        return [
            'coverage' => [
                'label' => 'Cobertura',
                'value' => $coverage . '%',
                'status' => $coverage < 90 ? 'danger' : ($coverage < 95 ? 'warning' : 'success'),
                'delta' => '0.0%',
                'icon' => 'users',
            ],
            'adherence' => [
                'label' => 'Adherencia',
                'value' => $adherence . '%',
                'status' => $adherence < 85 ? 'danger' : ($adherence < 92 ? 'warning' : 'success'),
                'delta' => '0.0%',
                'icon' => 'clock',
            ],
            'occupancy' => [
                'label' => 'Ocupación',
                'value' => round($occupancy, 1) . '%',
                'status' => $occupancy > 90 ? 'danger' : ($occupancy > 85 ? 'warning' : 'success'),
                'delta' => '0.0%',
                'icon' => 'chart-bar',
            ],
            'service_level' => [
                'label' => 'Nivel de Servicio',
                'value' => round($serviceLevel, 1) . '%',
                'status' => $serviceLevel < 80 ? 'danger' : ($serviceLevel < 90 ? 'warning' : 'success'),
                'delta' => '0.0%',
                'icon' => 'phone',
            ],
            'absenteeism' => [
                'label' => 'Ausentismo',
                'value' => $absenteeism . '%',
                'status' => $absenteeism > 5 ? 'danger' : 'success',
                'delta' => '0.0%',
                'icon' => 'user-minus',
            ],
            'shrinkage' => [
                'label' => 'Reductores (Shrink)',
                'value' => $shrinkage . '%',
                'status' => 'neutral',
                'delta' => '0.0%',
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
            if ($state) $inState++;
        }
        return round(($inState / $scheduled->count()) * 100, 1);
    }

    private function calculateOccupancy(array $operatorIds): float
    {
        $states = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->whereIn('current_state', ['READY', 'TALKING', 'WORK', 'WORK_READY', 'RESERVED'])
            ->get();

        $productive = $states->whereIn('current_state', ['TALKING', 'WORK', 'WORK_READY', 'RESERVED'])->count();
        $total = $states->count();

        return $total > 0 ? ($productive / $total) * 100 : 0;
    }
}
