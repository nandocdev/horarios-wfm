<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Services\CallCenterAnalyticsService;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Carbon;

class GetGeneralDashboardDataAction
{
    public function getMetrics(Employee $employee, string $dateRange, CallCenterAnalyticsService $service): array
    {
        [$start, $end] = $this->resolveBoundaries($dateRange);
        $allowedIds = $this->getAllowedEmployeeIds($employee);

        if (is_array($allowedIds) && empty($allowedIds)) {
            return [
                'total_volume' => 0,
                'abandon_rate' => 0,
                'sla' => 0,
                'total_handled' => 0,
            ];
        }

        return $service->getSummaryMetrics(
            dateFrom: $start,
            dateTo: $end,
            employeeIds: $allowedIds,
        );
    }

    public function getTopPerformers(Employee $employee, string $dateRange, CallCenterAnalyticsService $service): array
    {
        [$start, $end] = $this->resolveBoundaries($dateRange);
        $allowedIds = $this->getAllowedEmployeeIds($employee);

        if (is_array($allowedIds) && empty($allowedIds)) {
            return [];
        }

        $rows = $service->getTopAgentsToday(
            limit: 5,
            dateFrom: $start,
            dateTo: $end,
            employeeIds: $allowedIds,
        );

        return array_map(fn ($row) => (object) [
            'employee' => (object) ['full_name' => $row->agent_name],
            'total_calls' => (int) $row->total_calls,
            'avg_tmo' => (float) $row->avg_talk_time,
        ], $rows);
    }

    public function getAllowedEmployeeIds(Employee $employee): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->can('viewAny', CallRecord::class)) {
            return null;
        }

        $subordinateIds = $employee->getAllSubordinateIds();
        $subordinateIds[] = $employee->id;

        return $subordinateIds;
    }

    private function resolveBoundaries(string $dateRange): array
    {
        return match ($dateRange) {
            'today' => [Carbon::today()->toDateString(), Carbon::tomorrow()->toDateString()],
            'this_week' => [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->addDay()->toDateString()],
            'this_month' => [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->addDay()->toDateString()],
            default => [Carbon::today()->toDateString(), Carbon::tomorrow()->toDateString()],
        };
    }
}
