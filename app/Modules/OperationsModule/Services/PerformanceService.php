<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Services;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\OperationsModule\Actions\CalculateRealAdherenceAction;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use App\Shared\Support\Metrics\MetricFormulas;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class PerformanceService
{
    public function __construct(
        private readonly CalculateRealAdherenceAction $adherenceAction,
        private readonly EmployeeRepositoryInterface $employeeRepo,
    ) {}

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
    /**
     * Calcula los KPIs globales para el Dashboard.
     */
    public function getGlobalHeroKpis(?CarbonInterface $targetDate = null): array
    {
        $date = $targetDate ?? now();
        $dateStr = $date->toDateString();

        // 1. Si no es hoy, cachear el resultado completo por 24 horas (86400 segundos)
        if (! $date->isToday()) {
            return Cache::remember("wfm:hero_kpis:historical:{$dateStr}", 86400, function () use ($date) {
                return $this->resolveHeroKpisData($date);
            });
        }

        // 2. Si es hoy, resolver los Hero KPIs (ayer se cacheará internamente por 1 hora)
        return $this->resolveHeroKpisData($date);
    }

    /**
     * Resuelve los datos de Hero KPIs.
     */
    private function resolveHeroKpisData(CarbonInterface $date): array
    {
        $operatorIds = array_map(
            fn ($e) => $e->getId(),
            $this->employeeRepo->findActiveByPositions([1, 2, 5, 11, 13]),
        );

        if (empty($operatorIds)) {
            return [];
        }

        $current = $this->calculateMetrics($date, $operatorIds);

        $yesterday = $date->copy()->subDay();
        $yesterdayStr = $yesterday->toDateString();

        // Cachear las métricas de ayer por 1 hora (3600 segundos)
        $previous = Cache::remember("wfm:hero_kpis_metrics:historical:{$yesterdayStr}", 3600, function () use ($yesterday, $operatorIds) {
            return $this->calculateMetrics($yesterday, $operatorIds);
        });

        return [
            'coverage' => [
                'label' => 'Cobertura',
                'value' => round($current['coverage'], 1).'%',
                'status' => $current['coverage'] < 90 ? 'danger' : ($current['coverage'] < 95 ? 'warning' : 'success'),
                'delta' => $this->formatDelta($current['coverage'], $previous['coverage']),
                'icon' => 'users',
            ],
            'adherence' => [
                'label' => 'Adherencia',
                'value' => round($current['adherence'], 1).'%',
                'status' => $current['adherence'] < 85 ? 'danger' : ($current['adherence'] < 92 ? 'warning' : 'success'),
                'delta' => $this->formatDelta($current['adherence'], $previous['adherence']),
                'icon' => 'clock',
            ],
            'occupancy' => [
                'label' => 'Ocupación',
                'value' => round($current['occupancy'], 1).'%',
                'status' => $current['occupancy'] > 90 ? 'danger' : ($current['occupancy'] > 85 ? 'warning' : 'success'),
                'delta' => $this->formatDelta($current['occupancy'], $previous['occupancy']),
                'icon' => 'chart-bar',
            ],
            'service_level' => [
                'label' => 'Nivel de Servicio',
                'value' => round($current['service_level'], 1).'%',
                'status' => $current['service_level'] < 80 ? 'danger' : ($current['service_level'] < 90 ? 'warning' : 'success'),
                'delta' => $this->formatDelta($current['service_level'], $previous['service_level']),
                'icon' => 'phone',
            ],
            'absenteeism' => [
                'label' => 'Ausentismo',
                'value' => round($current['absenteeism'], 1).'%',
                'status' => $current['absenteeism'] > 5 ? 'danger' : 'success',
                'delta' => $this->formatDelta($current['absenteeism'], $previous['absenteeism'], true),
                'icon' => 'user-minus',
            ],
            'shrinkage' => [
                'label' => 'Reductores (Shrink)',
                'value' => round($current['shrinkage'], 1).'%',
                'status' => 'neutral',
                'delta' => $this->formatDelta($current['shrinkage'], $previous['shrinkage'], true),
                'icon' => 'scissors',
            ],
        ];
    }

    /**
     * Calcula las métricas para una fecha específica.
     */
    private function calculateMetrics(CarbonInterface $date, array $operatorIds): array
    {
        if ($date->isToday()) {
            return $this->calculateRealtimeMetrics($operatorIds);
        }

        return $this->calculateHistoricalMetrics($date, $operatorIds);
    }

    /**
     * Lógica para el cálculo en tiempo real (basado en estados actuales).
     */
    private function calculateRealtimeMetrics(array $operatorIds): array
    {
        $now = now();
        $today = $now->toDateString();

        // 1. Programados actualmente
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

        // 2. Conectados actualmente
        $realtimeStates = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])
            ->get();

        $totalConnected = $realtimeStates->count();

        // 3. Cálculos en tiempo real
        $coverage = $totalScheduled > 0 ? ($totalConnected / $totalScheduled) * 100 : 0.0;

        $adherenceRes = $this->adherenceAction->executeBatch($operatorIds, $now);
        $adherence = $adherenceRes['percentage'];

        $occupancy = $this->calculateRealtimeOccupancy($operatorIds);
        $serviceLevel = (float) (DB::table('csq_realtime_stats')->avg('service_level_long_term') ?? 0);

        // 4. Cachear ausentismo de hoy por 120 segundos para evitar sobrecarga
        $absenteeism = Cache::remember('wfm:realtime:absenteeism', 120, function () use ($operatorIds, $now, $today) {
            $idsWithEx = ScheduleException::whereIn('employee_id', $operatorIds)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->pluck('employee_id')
                ->toArray();

            $sched = WeeklyScheduleAssignment::whereIn('employee_id', $operatorIds)
                ->whereNotIn('employee_id', $idsWithEx)
                ->where('day_of_week', $now->dayOfWeekIso)
                ->whereHas('weeklySchedule', function ($q) use ($today) {
                    $q->where('week_start_date', '<=', $today)
                        ->where('week_end_date', '>=', $today);
                })
                ->where('start_time', '<=', $now->toTimeString())
                ->where('end_time', '>=', $now->toTimeString())
                ->get();

            $totalSched = $sched->count();

            $states = AgentRealtimeState::whereIn('employee_id', $operatorIds)
                ->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])
                ->get();

            $connectedFromSched = $states->whereIn('employee_id', $sched->pluck('employee_id'))->count();

            return (float) MetricFormulas::absenteeismRate(
                (float) MetricFormulas::absentPersonnel($totalSched, $connectedFromSched),
                (float) $totalSched
            );
        });

        // 5. Cachear shrinkage de hoy por 120 segundos
        $shrinkage = Cache::remember('wfm:realtime:shrinkage', 120, function () use ($operatorIds, $now) {
            return (float) $this->calculateShrinkage($operatorIds, $now);
        });

        return [
            'coverage' => min(100, $coverage),
            'adherence' => $adherence,
            'occupancy' => $occupancy,
            'service_level' => $serviceLevel,
            'absenteeism' => $absenteeism,
            'shrinkage' => $shrinkage,
        ];
    }

    /**
     * Lógica para el cálculo histórico (basado en registros y transiciones).
     */
    private function calculateHistoricalMetrics(CarbonInterface $date, array $operatorIds): array
    {
        $formattedDate = $date->toDateString();

        // 1. Service Level
        $callStats = DB::table('call_records')
            ->whereNotNull('queue_id')
            ->whereDate('ivr_started_at', $formattedDate)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled')
            )
            ->first();
        $sl = $callStats->total > 0 ? ($callStats->handled / $callStats->total) * 100 : 0.0;

        // 2. Ocupación (basada en duraciones de transiciones)
        $transitions = DB::table('agent_state_transitions')
            ->whereIn('employee_id', $operatorIds)
            ->whereDate('transition_time', $formattedDate)
            ->get();

        $durations = $transitions->groupBy(fn ($t) => strtoupper(trim($t->agent_state)))
            ->map(fn ($group) => $group->sum('duration'));

        $productive = ($durations['TALKING'] ?? 0) + ($durations['WORK'] ?? 0) + ($durations['RESERVED'] ?? 0);
        $ready = $durations['READY'] ?? 0;
        $occupancy = ($productive + $ready) > 0 ? ($productive / ($productive + $ready)) * 100 : 0.0;

        // 3. Cobertura (Promedio diario estimado o conteo único de conectados vs programados)
        $connectedCount = $transitions->whereNotIn('agent_state', ['Logout', 'Logged-in'])->pluck('employee_id')->unique()->count();
        $scheduledCount = WeeklyScheduleAssignment::whereIn('employee_id', $operatorIds)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->whereHas('weeklySchedule', function ($q) use ($formattedDate) {
                $q->where('week_start_date', '<=', $formattedDate)
                    ->where('week_end_date', '>=', $formattedDate);
            })
            ->count();
        $coverage = $scheduledCount > 0 ? ($connectedCount / $scheduledCount) * 100 : 100.0;

        // 4. Ausentismo
        $absenteeism = $scheduledCount > 0 ? max(0, ($scheduledCount - $connectedCount) / $scheduledCount * 100) : 0.0;

        // 5. Adherencia Real Histórica
        $adherenceRes = $this->adherenceAction->executeBatch($operatorIds, $date);

        return [
            'coverage' => min(100, $coverage),
            'adherence' => $adherenceRes['percentage'],
            'occupancy' => $occupancy,
            'service_level' => $sl,
            'absenteeism' => $absenteeism,
            'shrinkage' => $this->calculateShrinkage($operatorIds, $date),
        ];
    }

    private function formatDelta(float $current, float $previous, bool $inverse = false): string
    {
        $diff = $current - $previous;
        if (abs($diff) < 0.01) {
            return '0.0%';
        }

        $sign = $diff > 0 ? '+' : '';

        return $sign.round($diff, 1).'%';
    }

    private function calculateRealtimeOccupancy(array $operatorIds): float
    {
        $states = AgentRealtimeState::whereIn('employee_id', $operatorIds)
            ->whereIn('current_state', ['READY', 'TALKING', 'WORK', 'WORK_READY', 'RESERVED'])
            ->get();

        $productive = $states->whereIn('current_state', ['TALKING', 'WORK', 'WORK_READY', 'RESERVED'])->count();
        $total = $states->count();

        return $total > 0 ? ($productive / $total) * 100 : 0;
    }
}
