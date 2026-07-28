<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Actions;

use App\Modules\AnalyticsModule\Models\HistoricalShrinkage;
use App\Modules\AnalyticsModule\Models\ShrinkageCategory;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CalculateShrinkageAction
{
    private ?Collection $categoryCache = null;

    private ?Collection $activityTypeMapping = null;

    private ?Collection $absenceReasonMapping = null;

    public function execute(CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        $this->loadMappings();

        $processed = [
            'schedule_exceptions' => 0,
            'leave_requests' => 0,
            'intraday_activities' => 0,
            'total_records' => 0,
        ];

        DB::transaction(function () use ($startDate, $endDate, &$processed) {
            $batch = [];

            $scheduleExceptions = $this->loadScheduleExceptions($startDate, $endDate);
            foreach ($scheduleExceptions as $exception) {
                $records = $this->processException($exception, $startDate, $endDate);
                foreach ($records as $record) {
                    $batch[] = $record;
                }
                $processed['schedule_exceptions']++;
            }

            $leaveRequests = $this->loadLeaveRequests($startDate, $endDate);
            foreach ($leaveRequests as $leave) {
                $records = $this->processLeave($leave, $startDate, $endDate);
                foreach ($records as $record) {
                    $batch[] = $record;
                }
                $processed['leave_requests']++;
            }

            $activities = $this->loadIntradayActivities($startDate, $endDate);
            foreach ($activities as $activity) {
                $record = $this->processActivity($activity);
                if ($record !== null) {
                    $batch[] = $record;
                }
                $processed['intraday_activities']++;
            }

            if (! empty($batch)) {
                HistoricalShrinkage::upsert(
                    $batch,
                    ['employee_id', 'interval_start', 'source_type', 'source_id'],
                    ['duration_minutes', 'shrinkage_category_id', 'date', 'interval_end', 'notes', 'metadata'],
                );
                $processed['total_records'] = count($batch);
            }
        });

        return $processed;
    }

    public function executeForEmployee(int $employeeId, CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        $this->loadMappings();

        $processed = ['schedule_exceptions' => 0, 'leave_requests' => 0, 'intraday_activities' => 0, 'total_records' => 0];

        DB::transaction(function () use ($employeeId, $startDate, $endDate, &$processed) {
            $batch = [];

            $exceptions = ScheduleException::where('employee_id', $employeeId)
                ->whereDate('start_at', '<=', $endDate->toDateString())
                ->whereDate('end_at', '>=', $startDate->toDateString())
                ->get();

            foreach ($exceptions as $exception) {
                $batch = array_merge($batch, $this->processException($exception, $startDate, $endDate));
                $processed['schedule_exceptions']++;
            }

            $leaves = LeaveRequest::where('employee_id', $employeeId)
                ->whereIn('status', ['approved', 'aprobado'])
                ->whereDate('start_time', '<=', $endDate->toDateString())
                ->whereDate('end_time', '>=', $startDate->toDateString())
                ->get();

            foreach ($leaves as $leave) {
                $batch = array_merge($batch, $this->processLeave($leave, $startDate, $endDate));
                $processed['leave_requests']++;
            }

            $activities = IntradayActivity::where('employee_id', $employeeId)
                ->get();

            foreach ($activities as $activity) {
                $record = $this->processActivity($activity);
                if ($record !== null) {
                    $batch[] = $record;
                }
                $processed['intraday_activities']++;
            }

            if (! empty($batch)) {
                HistoricalShrinkage::upsert(
                    $batch,
                    ['employee_id', 'interval_start', 'source_type', 'source_id'],
                    ['duration_minutes', 'shrinkage_category_id', 'date', 'interval_end', 'notes', 'metadata'],
                );
                $processed['total_records'] = count($batch);
            }
        });

        return $processed;
    }

    private function loadScheduleExceptions(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return ScheduleException::whereDate('start_at', '<=', $end->toDateString())
            ->whereDate('end_at', '>=', $start->toDateString())
            ->get();
    }

    private function loadLeaveRequests(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return LeaveRequest::whereIn('status', ['approved', 'aprobado'])
            ->whereDate('start_time', '<=', $end->toDateString())
            ->whereDate('end_time', '>=', $start->toDateString())
            ->get();
    }

    private function loadIntradayActivities(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return IntradayActivity::whereHas('approvedPeriod', function ($q) use ($start, $end) {
            $q->whereDate('start_time', '<=', $end->toDateString())
                ->whereDate('end_time', '>=', $start->toDateString());
        })->orWhereDoesntHave('approvedPeriod')
            ->get();
    }

    private function processException(ScheduleException $exception, CarbonInterface $start, CarbonInterface $end): array
    {
        $category = $this->mapAbsenceReason($exception->reason);
        if ($category === null) {
            return [];
        }

        $records = [];
        $current = $exception->start_at->copy()->startOfDay();
        $exceptionEnd = $exception->end_at->copy()->endOfDay();
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->endOfDay();

        $startDay = $current->max($rangeStart);
        $endDay = $exceptionEnd->min($rangeEnd);

        while ($startDay->lte($endDay)) {
            $dayEnd = $startDay->copy()->endOfDay();
            $blockStart = $exception->start_at->max($startDay);
            $blockEnd = $exception->end_at->min($dayEnd);

            $minutes = (int) ceil($blockStart->diffInMinutes($blockEnd));

            if ($minutes > 0) {
                $records[] = [
                    'employee_id' => $exception->employee_id,
                    'shrinkage_category_id' => $category->id,
                    'date' => $startDay->toDateString(),
                    'interval_start' => $blockStart,
                    'interval_end' => $blockEnd,
                    'duration_minutes' => $minutes,
                    'source_type' => 'schedule_exception',
                    'source_id' => (string) $exception->id,
                    'notes' => $exception->remarks,
                    'metadata' => json_encode([
                        'absence_reason_code_id' => $exception->absence_reason_code_id,
                        'is_full_day' => $exception->is_full_day,
                    ]),
                ];
            }

            $startDay->addDay();
        }

        return $records;
    }

    private function processLeave(LeaveRequest $leave, CarbonInterface $start, CarbonInterface $end): array
    {
        $category = $this->mapLeaveType($leave->type);
        if ($category === null) {
            return [];
        }

        $records = [];
        $current = $leave->start_time->copy()->startOfDay();
        $leaveEnd = $leave->end_time->copy()->endOfDay();
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->endOfDay();

        $startDay = $current->max($rangeStart);
        $endDay = $leaveEnd->min($rangeEnd);

        while ($startDay->lte($endDay)) {
            $dayEnd = $startDay->copy()->endOfDay();
            $blockStart = $leave->start_time->max($startDay);
            $blockEnd = $leave->end_time->min($dayEnd);

            $minutes = (int) ceil($blockStart->diffInMinutes($blockEnd));

            if ($minutes > 0) {
                $records[] = [
                    'employee_id' => $leave->employee_id,
                    'shrinkage_category_id' => $category->id,
                    'date' => $startDay->toDateString(),
                    'interval_start' => $blockStart,
                    'interval_end' => $blockEnd,
                    'duration_minutes' => $minutes,
                    'source_type' => 'leave_request',
                    'source_id' => (string) $leave->id,
                    'notes' => $leave->reason,
                    'metadata' => json_encode(['leave_type' => $leave->type]),
                ];
            }

            $startDay->addDay();
        }

        return $records;
    }

    private function processActivity(IntradayActivity $activity): ?array
    {
        $activityType = $activity->activityType;
        if (! $activityType) {
            return null;
        }

        $category = $this->mapActivityType($activityType);
        if ($category === null) {
            return null;
        }

        $start = $activity->getRangeStart();
        $end = $activity->getRangeEnd();

        if (! $start || ! $end) {
            return null;
        }

        $minutes = (int) ceil($start->diffInMinutes($end));

        return [
            'employee_id' => $activity->employee_id,
            'shrinkage_category_id' => $category->id,
            'date' => $start->toDateString(),
            'interval_start' => $start,
            'interval_end' => $end,
            'duration_minutes' => $minutes,
            'source_type' => 'intraday_activity',
            'source_id' => (string) $activity->id,
            'notes' => $activity->notes,
            'metadata' => json_encode([
                'activity_type_id' => $activity->activity_type_id,
                'activity_type_name' => $activityType->name,
            ]),
        ];
    }

    private function loadMappings(): void
    {
        if ($this->categoryCache === null) {
            $this->categoryCache = ShrinkageCategory::all()->keyBy('code');
        }

        if ($this->activityTypeMapping === null) {
            $this->activityTypeMapping = ActivityType::all()->mapWithKeys(function ($type) {
                $code = $this->inferCategoryFromName($type->name, [
                    'almuerzo' => 'lunch',
                    'lunch' => 'lunch',
                    'break' => 'break',
                    'descanso' => 'break',
                    'reunion' => 'meeting',
                    'meeting' => 'meeting',
                    'coaching' => 'coaching',
                    'retroalimentacion' => 'coaching',
                    'capacitacion' => 'training',
                    'training' => 'training',
                ]);

                return [$type->id => $code];
            });
        }

        if ($this->absenceReasonMapping === null) {
            $this->absenceReasonMapping = AbsenceReasonCode::all()->mapWithKeys(function ($reason) {
                $code = $this->inferCategoryFromName($reason->name, [
                    'vacacion' => 'vacation',
                    'vacaciones' => 'vacation',
                    'capacitacion' => 'training',
                    'training' => 'training',
                    'medico' => 'leave',
                    'personal' => 'leave',
                    'permiso' => 'leave',
                    'ausencia' => 'absence',
                    'injustificada' => 'absence',
                ]);

                return [$reason->id => $code];
            });
        }
    }

    private function inferCategoryFromName(string $name, array $mappings): ?string
    {
        $lower = mb_strtolower($name);

        foreach ($mappings as $keyword => $code) {
            if (str_contains($lower, $keyword)) {
                return $code;
            }
        }

        return null;
    }

    private function mapAbsenceReason(?AbsenceReasonCode $reason): ?ShrinkageCategory
    {
        if (! $reason || $this->absenceReasonMapping === null || $this->categoryCache === null) {
            return null;
        }

        $code = $this->absenceReasonMapping->get($reason->id);

        return $code ? $this->categoryCache->get($code) : null;
    }

    private function mapLeaveType(string $type): ?ShrinkageCategory
    {
        if ($this->categoryCache === null) {
            return null;
        }

        $code = match (mb_strtolower($type)) {
            'vacation', 'vacaciones' => 'vacation',
            'sick', 'medical', 'medico', 'personal', 'familia' => 'leave',
            'training', 'capacitacion' => 'training',
            default => 'leave',
        };

        return $this->categoryCache->get($code);
    }

    private function mapActivityType(ActivityType $activityType): ?ShrinkageCategory
    {
        if ($this->activityTypeMapping === null || $this->categoryCache === null) {
            return null;
        }

        $code = $this->activityTypeMapping->get($activityType->id);

        return $code ? $this->categoryCache->get($code) : null;
    }
}
