<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Services;

use App\Modules\OperationsModule\Actions\CalculateRealAdherenceAction;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Support\Metrics\MetricFormulas;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Support\Facades\Cache;

final class PerformanceService
{
    public function __construct(
        private readonly CalculateRealAdherenceAction $adherenceAction,
        private readonly EmployeeRepositoryInterface $employeeRepo,
        private readonly DashboardScheduleQueriesInterface $scheduleQueries,
        private readonly TelemetryRealtimeRepositoryInterface $realtimeRepo,
        private readonly CachePolicyService $cachePolicy,
    ) {}

    /**
     * Calcula el Shrinkage (Reductores) dinámico para un grupo de empleados en una fecha.
     */
    public function calculateShrinkage(array $employeeIds, CarbonInterface $date): float
    {
        $carbonDate = Carbon::instance($date);
        $startOfDay = $carbonDate->copy()->startOfDay();
        $endOfDay = $carbonDate->copy()->endOfDay();

        // 1. Minutos por Excepciones (Permisos, Vacaciones, Incapacidades)
        $exceptions = $this->scheduleQueries->getExceptionsForRange($employeeIds, $startOfDay, $endOfDay);

        $exceptionMinutes = $exceptions->sum(function ($ex) use ($startOfDay, $endOfDay) {
            $start = $ex->start_at->max($startOfDay);
            $end = $ex->end_at->min($endOfDay);

            return max(0, $start->diffInMinutes($end));
        });

        // 2. Minutos por Actividades Intradía (Reuniones, Coaching, etc.)
        $intradayActivities = $this->scheduleQueries->getOverlappingIntradayActivities($employeeIds, $startOfDay, $endOfDay);

        $intradayMinutes = $intradayActivities->sum(function ($activity) use ($startOfDay, $endOfDay) {
            $start = $activity->getRangeStart()?->max($startOfDay);
            $end = $activity->getRangeEnd()?->min($endOfDay);

            return $start && $end ? max(0, $start->diffInMinutes($end)) : 0;
        });

        // 3. Minutos Totales Programados (Jornada Bruta)
        $assignments = $this->scheduleQueries->getScheduledAssignmentsWithSchedule(
            $employeeIds,
            $date->toDateString(),
            $date->dayOfWeekIso,
        );

        $totalScheduledMinutes = $assignments->sum(function ($assignment) {
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
        $dateStr = $date->toDateString();

        if (! $date->isToday()) {
            return $this->cachePolicy->remember('operations', 'historical', "hero_kpis:{$dateStr}", function () use ($date) {
                return $this->resolveHeroKpisData($date);
            });
        }

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

        $previous = $this->cachePolicy->remember('operations', 'historical', "hero_kpis_metrics:{$yesterdayStr}", function () use ($yesterday, $operatorIds) {
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
        $time = $now->toTimeString();

        // 1. Programados actualmente (excluyendo excepciones activas)
        $activeExceptionIds = $this->scheduleQueries->getActiveExceptionIds($operatorIds, $now);

        $scheduled = $this->scheduleQueries->getScheduledForTime($operatorIds, $today, $now->dayOfWeekIso, $time)
            ->reject(fn ($s) => in_array($s->employee_id, $activeExceptionIds));

        $totalScheduled = $scheduled->count();

        // 2. Conectados actualmente
        $states = $this->realtimeRepo->getRealtimeStates($operatorIds);
        $connected = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN']);
        $totalConnected = $connected->count();

        // 3. Cálculos en tiempo real
        $coverage = $totalScheduled > 0 ? ($totalConnected / $totalScheduled) * 100 : 0.0;

        $adherenceRes = $this->adherenceAction->executeBatch($operatorIds, $now);
        $adherence = $adherenceRes['percentage'];

        $occupancy = $this->calculateRealtimeOccupancy($states);
        $serviceLevel = $this->realtimeRepo->getAverageServiceLevel();

        // 4. Cachear ausentismo de hoy por 120 segundos (realtime TTL)
        $absenteeism = $this->cachePolicy->remember('operations', 'realtime', 'absenteeism', function () use ($operatorIds, $scheduled, $totalScheduled) {
            $connectedFromSched = $this->realtimeRepo->getRealtimeStates($operatorIds)
                ->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])
                ->whereIn('employee_id', $scheduled->pluck('employee_id'))
                ->count();

            return (float) MetricFormulas::absenteeismRate(
                (float) MetricFormulas::absentPersonnel($totalScheduled, $connectedFromSched),
                (float) $totalScheduled
            );
        });

        // 5. Cachear shrinkage de hoy por 120 segundos (realtime TTL)
        $shrinkage = $this->cachePolicy->remember('operations', 'realtime', 'shrinkage', function () use ($operatorIds, $now) {
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
        $callStats = $this->realtimeRepo->getCallStatsForDate($formattedDate);
        $sl = $callStats->total > 0 ? ($callStats->handled / $callStats->total) * 100 : 0.0;

        // 2. Ocupación (basada en duraciones de transiciones)
        $transitions = $this->realtimeRepo->getBatchStateTransitions($operatorIds, $formattedDate);

        $durations = $transitions->groupBy(fn ($t) => strtoupper(trim($t->agent_state)))
            ->map(fn ($group) => $group->sum('duration'));

        $productive = ($durations['TALKING'] ?? 0) + ($durations['WORK'] ?? 0) + ($durations['RESERVED'] ?? 0);
        $ready = $durations['READY'] ?? 0;
        $occupancy = ($productive + $ready) > 0 ? ($productive / ($productive + $ready)) * 100 : 0.0;

        // 3. Cobertura
        $connectedCount = $transitions->whereNotIn('agent_state', ['Logout', 'Logged-in'])->pluck('employee_id')->unique()->count();
        $scheduledCount = $this->scheduleQueries->getScheduledCountForDay($operatorIds, $formattedDate, $date->dayOfWeekIso);
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

    private function calculateRealtimeOccupancy(Collection $states): float
    {
        $occupancyStates = $states->whereIn('current_state', ['READY', 'TALKING', 'WORK', 'WORK_READY', 'RESERVED']);

        $productive = $occupancyStates->whereIn('current_state', ['TALKING', 'WORK', 'WORK_READY', 'RESERVED'])->count();
        $total = $occupancyStates->count();

        return $total > 0 ? ($productive / $total) * 100 : 0;
    }
}
