<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Repositories;

use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\OperationalSetting;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentDashboardScheduleQueries implements DashboardScheduleQueriesInterface
{
    public function getCurrentWeek(string $today): ?object
    {
        return WeeklySchedule::where('week_start_date', '<=', $today)
            ->where('week_end_date', '>=', $today)
            ->first();
    }

    public function getScheduledCount(?array $employeeIds, string $today, int $dayOfWeek): int
    {
        $query = WeeklyScheduleAssignment::query()
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            );

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->count();
    }

    public function getExceptionCount(?array $employeeIds, string $today): int
    {
        $query = ScheduleException::whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today);

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->count();
    }

    public function getLeaveCounts(?array $employeeIds, string $today): array
    {
        $approvedQuery = LeaveRequest::whereDate('start_time', '<=', $today)
            ->whereDate('end_time', '>=', $today)
            ->where('status', 'approved');

        $pendingQuery = LeaveRequest::where('status', 'pending');

        if ($employeeIds !== null) {
            $approvedQuery->whereIn('employee_id', $employeeIds);
            $pendingQuery->whereIn('employee_id', $employeeIds);
        }

        return [
            'approved' => $approvedQuery->count(),
            'pending' => $pendingQuery->count(),
        ];
    }

    public function getActiveIntradayCount(?array $employeeIds, string $nowIso): int
    {
        $query = IntradayActivity::whereRaw('time_range @> ?::timestamptz', [$nowIso]);

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->count();
    }

    public function getTodayIntradayCount(?array $employeeIds, string $today): int
    {
        $query = IntradayActivity::whereDate('created_at', $today);

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->count();
    }

    public function getUpcomingEvents(?array $employeeIds, string $today, int $limit = 6): Collection
    {
        $query = IntradayActivity::with('activityType', 'employee.team')
            ->whereDate('created_at', $today)
            ->orderBy('time_range');

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->take($limit)->get()->map(fn ($a): array => [
            'time' => $a->getRangeStart()?->format('H:i') ?? '--:--',
            'title' => $a->activityType?->name ?? 'Actividad',
            'detail' => ($a->employee?->team?->name ?? '').($a->employee ? ' · '.$a->employee->full_name : ''),
        ]);
    }

    public function getPendingSwapCount(?array $employeeIds): int
    {
        $query = ShiftSwapRequest::where('status', 'pending');

        if ($employeeIds !== null) {
            $query->whereIn('requester_id', $employeeIds);
        }

        return $query->count();
    }

    public function getCoverageSlots(?array $employeeIds, string $today): Collection
    {
        $slots = collect();
        for ($h = 6; $h <= 17; $h++) {
            $hourSlot = $h < 10 ? "0{$h}:00" : "{$h}:00";
            $query = WeeklyScheduleAssignment::where('start_time', '<=', $hourSlot)
                ->where('end_time', '>=', $hourSlot)
                ->whereHas('weeklySchedule', fn ($q) => $q
                    ->where('week_start_date', '<=', $today)
                    ->where('week_end_date', '>=', $today)
                );

            if ($employeeIds !== null) {
                $query->whereIn('employee_id', $employeeIds);
            }

            $slots->push(['hour' => (string) $h, 'assigned' => $query->count()]);
        }

        return $slots;
    }

    public function getOperationalSettings(): array
    {
        return OperationalSetting::pluck('value', 'key')->toArray();
    }

    public function getAbsenceTrends(string $from, string $to): Collection
    {
        return DB::table('schedule_exceptions')
            ->whereDate('start_at', '>=', $from)
            ->whereDate('start_at', '<=', $to)
            ->select(DB::raw('DATE(start_at) as date'), DB::raw('COUNT(*) as absences'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('absences');
    }

    public function getExceptionsForRange(array $employeeIds, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return ScheduleException::whereIn('employee_id', $employeeIds)
            ->where('start_at', '<=', $end)
            ->where('end_at', '>=', $start)
            ->get();
    }

    public function getOverlappingIntradayActivities(array $employeeIds, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return IntradayActivity::whereIn('employee_id', $employeeIds)
            ->whereRaw('time_range && tstzrange(?, ?)', [$start->toIso8601String(), $end->toIso8601String()])
            ->get();
    }

    public function getScheduledAssignmentsWithSchedule(array $employeeIds, string $today, int $dayOfWeek): Collection
    {
        return WeeklyScheduleAssignment::whereIn('employee_id', $employeeIds)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            )
            ->with('schedule')
            ->get();
    }

    public function getScheduledForTime(array $employeeIds, string $today, int $dayOfWeek, string $time): Collection
    {
        return WeeklyScheduleAssignment::whereIn('employee_id', $employeeIds)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            )
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->get();
    }

    public function getActiveExceptionIds(array $employeeIds, CarbonInterface $time): array
    {
        return ScheduleException::whereIn('employee_id', $employeeIds)
            ->where('start_at', '<=', $time)
            ->where('end_at', '>=', $time)
            ->pluck('employee_id')
            ->toArray();
    }

    public function getScheduledCountForDay(array $employeeIds, string $today, int $dayOfWeek): int
    {
        return WeeklyScheduleAssignment::whereIn('employee_id', $employeeIds)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            )
            ->count();
    }
}
