<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GetAgentDashboardDataAction
{
    public function execute(Employee $employee, string $dateRange): array
    {
        [$start, $end] = $this->resolveBoundaries($dateRange);

        $stats = CallRecord::where('employee_id', $employee->id)
            ->whereBetween('ivr_started_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN contact_disposition = 1 OR status = \'abandoned\' THEN 1 ELSE 0 END) as abandoned'),
                DB::raw('AVG(talk_time) as avg_talk_time'),
                DB::raw('AVG(talk_time + work_time) as avg_handle_time')
            )->first();

        return [
            'total_calls' => (int) ($stats->total_calls ?? 0),
            'abandoned' => (int) ($stats->abandoned ?? 0),
            'avg_talk_time' => round((float) ($stats->avg_talk_time ?? 0), 0),
            'avg_handle_time' => round((float) ($stats->avg_handle_time ?? 0), 0),
        ];
    }

    public function getRecentCalls(Employee $employee, string $dateRange)
    {
        [$start, $end] = $this->resolveBoundaries($dateRange);

        return CallRecord::with(['queue', 'caseSubtype'])
            ->where('employee_id', $employee->id)
            ->whereBetween('ivr_started_at', [$start, $end])
            ->orderByDesc('ivr_started_at')
            ->paginate(10);
    }

    private function resolveBoundaries(string $dateRange): array
    {
        return match ($dateRange) {
            'today' => [Carbon::today(), Carbon::tomorrow()],
            'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::today(), Carbon::tomorrow()],
        };
    }
}
