<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Services;

use App\Modules\OperationsModule\Actions\CalculateRealAdherenceAction;
use App\Modules\OperationsModule\Actions\GetEmployeePerformanceAction;
use App\Modules\OperationsModule\DTOs\AgentPerformanceSummaryDTO;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Shared\Contracts\Employees\EmployeeInterface;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Support\Cache\CachePolicyService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class AgentPerformanceService
{
    public function __construct(
        private readonly GetEmployeePerformanceAction $performanceAction,
        private readonly CalculateRealAdherenceAction $adherenceAction,
        private readonly EmployeeRepositoryInterface $employeeRepo,
        private readonly ScheduleServiceInterface $scheduleService,
        private readonly AgentPerformanceRepositoryInterface $performanceRepo,
        private readonly CachePolicyService $cachePolicy,
    ) {}

    public function getPerformance(EmployeeInterface $employee, int $days = 5): AgentPerformanceSummaryDTO
    {
        $dates = $this->resolveLastWorkedDates($employee, $days);

        if ($dates === []) {
            return AgentPerformanceSummaryDTO::empty();
        }

        $dayDTOs = [];
        $stateTotals = [];
        $queueAccumulator = [];
        $deviationRows = [];

        foreach ($dates as $date) {
            $dayData = $date->isToday()
                ? $this->performanceAction->execute($employee, $date)->toArray()
                : $this->cachePolicy->remember('operations', 'agent_performance', "{$employee->getId()}:{$date->toDateString()}", fn () => $this->performanceAction->execute($employee, $date)->toArray());

            if (is_object($dayData)) {
                $this->cachePolicy->flushByPattern('operations', 'agent_performance');
                $dayData = $this->performanceAction->execute($employee, $date)->toArray();
                Cache::put("ops:agent_performance:{$employee->getId()}:{$date->toDateString()}", $dayData, 3600);
            }

            $dayDTOs[] = $dayData;

            foreach ($dayData['activities'] as $state => $minutes) {
                $stateTotals[$state] = ($stateTotals[$state] ?? 0) + $minutes;
            }

            foreach ($dayData['queues'] as $queueName => $qData) {
                if (! isset($queueAccumulator[$queueName])) {
                    $queueAccumulator[$queueName] = ['total_calls' => 0, 'weighted_aht' => 0];
                }
                $queueAccumulator[$queueName]['total_calls'] += $qData['total_calls'];
                $queueAccumulator[$queueName]['weighted_aht'] += $qData['avg_handle_time'] * $qData['total_calls'];
            }

            $deviationRows[] = $this->buildDeviationRow($employee, $date, $dayData);
        }

        $summary = $this->computeSummary($dayDTOs);
        $stateDistribution = $this->computeStateDistribution($stateTotals);

        // Calcular comparativa con el equipo
        $startDateStr = $dates[0]->toDateString();
        $endDateStr = end($dates)->toDateString();

        $teamMembers = $this->employeeRepo->findByTeam($employee->getTeamId() ?? 0);
        $teamEmployeeIds = array_map(fn ($e) => $e->getId(), $teamMembers);

        if (empty($teamEmployeeIds)) {
            $teamEmployeeIds = array_map(
                fn ($e) => $e->getId(),
                $this->employeeRepo->findActive(),
            );
        }

        $teamStats = $this->performanceRepo->getTeamCallStats(
            $teamEmployeeIds,
            Carbon::parse($startDateStr),
            Carbon::parse($endDateStr),
        );
        $teamCallsCount = $teamStats['count'];
        $teamAvgCalls = count($teamEmployeeIds) > 0 ? (int) round($teamCallsCount / count($teamEmployeeIds)) : 0;

        $teamAvgAht = $teamCallsCount > 0
            ? (int) round(($teamStats['talk'] + $teamStats['work']) / $teamCallsCount)
            : 0;

        $teamMetrics = AgentDailyMetric::whereIn('employee_id', $teamEmployeeIds)
            ->whereBetween('metric_date', [$startDateStr, $endDateStr])
            ->selectRaw('AVG(availability_pct) as avg_adherence, AVG(efficiency_pct) as avg_occupancy, MAX(availability_pct) as best_adherence, MAX(efficiency_pct) as best_occupancy')
            ->first();

        $summary['team_comparison'] = [
            'calls' => $teamAvgCalls,
            'aht' => $teamAvgAht,
            'adherence' => $teamMetrics ? round((float) $teamMetrics->avg_adherence, 1) : 0,
            'occupancy' => $teamMetrics ? round((float) $teamMetrics->avg_occupancy, 1) : 0,
            'best' => [
                'calls' => (int) round($teamAvgCalls * 1.3) ?: 48,
                'aht' => (int) round($teamAvgAht * 0.82) ?: 290,
                'adherence' => $teamMetrics ? round((float) $teamMetrics->best_adherence, 1) : 0,
                'occupancy' => $teamMetrics ? round((float) $teamMetrics->best_occupancy, 1) : 0,
            ],
        ];

        $queueDetail = [];
        foreach ($queueAccumulator as $queueName => $q) {
            $queueDetail[] = [
                'name' => $queueName,
                'total_calls' => $q['total_calls'],
                'aht_seconds' => $q['total_calls'] > 0
                    ? (int) round($q['weighted_aht'] / $q['total_calls'])
                    : 0,
            ];
        }

        return new AgentPerformanceSummaryDTO(
            days: $dayDTOs,
            summary: $summary,
            stateDistribution: $stateDistribution,
            queueDetail: $queueDetail,
            deviations: $deviationRows,
        );
    }

    private function resolveLastWorkedDates(EmployeeInterface $employee, int $count): array
    {
        // Delegado al contrato WFM (ScheduleService): una única consulta por rango
        // en lugar de 2 queries por día recorriendo hasta 60 días.
        $dateStrings = $this->scheduleService->recentWorkedDates($employee->getId(), $count, Carbon::today());

        return array_map(fn (string $date) => Carbon::parse($date), $dateStrings);
    }

    private function computeSummary(array $days): array
    {
        $total = count($days);
        if ($total === 0) {
            return [
                'avg_adherence' => 0,
                'avg_occupancy' => 0,
                'total_calls' => 0,
                'avg_aht_seconds' => 0,
                'total_aux_minutes' => 0,
            ];
        }

        $adherenceSum = 0;
        $occupancySum = 0;
        $totalCalls = 0;
        $auxMinutes = 0;
        $adherenceCount = 0;
        $totalHandlingTime = 0;

        foreach ($days as $day) {
            if (($day['metrics']['total_connected_minutes'] ?? 0) > 0) {
                $adherenceSum += $day['metrics']['productivity_percentage'] ?? 0;
                $adherenceCount++;
            }
            if (($day['metrics']['productivity_percentage'] ?? 0) > 0) {
                $occupancySum += $day['metrics']['productivity_percentage'] ?? 0;
            }
            foreach ($day['queues'] ?? [] as $q) {
                $totalCalls += $q['total_calls'] ?? 0;
                $totalHandlingTime += ($q['avg_handle_time'] ?? 0) * ($q['total_calls'] ?? 0);
            }
            $auxMinutes += ($day['activities']['Not Ready'] ?? 0) + ($day['activities']['AUX'] ?? 0);
        }

        return [
            'avg_adherence' => $adherenceCount > 0 ? round($adherenceSum / $adherenceCount, 1) : 0,
            'avg_occupancy' => $total > 0 ? round($occupancySum / $total, 1) : 0,
            'total_calls' => $totalCalls,
            'avg_aht_seconds' => $totalCalls > 0 ? (int) round($totalHandlingTime / $totalCalls) : 0,
            'total_aux_minutes' => round($auxMinutes, 0),
        ];
    }

    private function computeStateDistribution(array $stateTotals): array
    {
        $labels = ['READY', 'TALKING', 'WORK', 'NOT_READY', 'LOGOUT', 'AUX'];
        $colors = ['#22c55e', '#3b82f6', '#8b5cf6', '#f59e0b', '#94a3b8', '#ef4444'];

        $totalMinutes = array_sum($stateTotals);
        if ($totalMinutes <= 0) {
            return [];
        }

        $distribution = [];
        foreach ($labels as $i => $label) {
            $minutes = $stateTotals[$label] ?? 0;
            $distribution[] = [
                'label' => $label,
                'minutes' => round($minutes, 0),
                'percentage' => round(($minutes / $totalMinutes) * 100, 1),
                'color' => $colors[$i] ?? '#94a3b8',
            ];
        }

        return $distribution;
    }

    private function buildDeviationRow(EmployeeInterface $employee, CarbonInterface $date, array $dayData): array
    {
        $attendance = $dayData['attendance'];

        $lateMinutes = 0;
        if ($attendance['status'] === 'tardanza') {
            $lateMinutes = max(0, $attendance['diff_minutes']);
        }

        $earlyExit = 0;
        $scheduledEnd = $attendance['scheduled_entry'] ?? null;

        $actualLogout = $this->performanceRepo->getStateTransitions($employee->getId(), $date)
            ->filter(fn ($t) => trim((string) $t->agent_state) === 'LOGOUT')
            ->sortByDesc(fn ($t) => $t->transition_time)
            ->first();

        if ($scheduledEnd && $actualLogout) {
            $scheduledEndTime = Carbon::parse($scheduledEnd)->setDate($date->year, $date->month, $date->day);
            $logoutTime = Carbon::parse($actualLogout->transition_time);
            $earlyExit = max(0, $scheduledEndTime->diffInMinutes($logoutTime, false));
        }

        return [
            'date' => $date->toDateString(),
            'label' => $date->locale('es')->isoFormat('ddd D/M'),
            'entry_status' => $attendance['status'],
            'late_minutes' => $lateMinutes,
            'aux_minutes' => round(($dayData['activities']['Not Ready'] ?? 0) + ($dayData['activities']['AUX'] ?? 0), 0),
            'early_exit_minutes' => $earlyExit,
            'connected_minutes' => $dayData['metrics']['total_connected_minutes'],
            'scheduled_minutes' => $dayData['metrics']['total_scheduled_minutes'],
        ];
    }
}
