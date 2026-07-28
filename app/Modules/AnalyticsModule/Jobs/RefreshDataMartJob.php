<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Jobs;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Skill;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RefreshDataMartJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly ?CarbonInterface $startDate = null,
        private readonly ?CarbonInterface $endDate = null,
    ) {
        $this->onQueue('wfm-heavy');
    }

    public function handle(): void
    {
        $start = $this->startDate ?? CarbonImmutable::yesterday()->startOfDay();
        $end = $this->endDate ?? CarbonImmutable::now()->endOfDay();

        Log::info('DataMart refresh started', ['from' => $start->toDateString(), 'to' => $end->toDateString()]);

        $this->refreshDimensions();
        $this->refreshFactCalls($start, $end);
        $this->refreshFactSchedule($start, $end);
        $this->refreshFactQuality($start, $end);
        $this->refreshFactAbsence($start, $end);
        $this->refreshFactAgentInterval($start, $end);

        Log::info('DataMart refresh completed');
    }

    private function refreshDimensions(): void
    {
        DB::statement('TRUNCATE TABLE dim_employee');
        DB::statement('TRUNCATE TABLE dim_team');
        DB::statement('TRUNCATE TABLE dim_department');
        DB::statement('TRUNCATE TABLE dim_queue');
        DB::statement('TRUNCATE TABLE dim_shift');
        DB::statement('TRUNCATE TABLE dim_skill');

        Employee::with(['team', 'department', 'position', 'manager'])->chunk(100, function ($employees) {
            $batch = [];
            foreach ($employees as $e) {
                $batch[] = [
                    'employee_id' => $e->id,
                    'employee_number' => $e->employee_number,
                    'full_name' => $e->full_name,
                    'email' => $e->email,
                    'team_id' => $e->team_id,
                    'team_name' => $e->team?->name,
                    'department_id' => $e->department_id,
                    'department_name' => $e->department?->name,
                    'position_id' => $e->position_id,
                    'position_name' => $e->position?->name,
                    'supervisor_id' => $e->parent_id,
                    'supervisor_name' => $e->manager?->full_name,
                    'hire_date' => $e->hire_date,
                    'is_active' => $e->is_active,
                    'is_manager' => $e->is_manager,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('dim_employee')->insert($batch);
        });

        Team::chunk(100, function ($teams) {
            $batch = [];
            foreach ($teams as $t) {
                $batch[] = [
                    'team_id' => $t->id,
                    'name' => $t->name,
                    'supervisor_id' => $t->supervisor_id,
                    'supervisor_name' => $t->supervisor?->full_name,
                    'is_active' => $t->is_active ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('dim_team')->insert($batch);
        });

        Department::with('directorate')->chunk(100, function ($departments) {
            $batch = [];
            foreach ($departments as $d) {
                $batch[] = [
                    'department_id' => $d->id,
                    'name' => $d->name,
                    'directorate_id' => $d->directorate_id,
                    'directorate_name' => $d->directorate?->name,
                    'is_active' => $d->is_active ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('dim_department')->insert($batch);
        });

        CallQueue::with('channel')->chunk(100, function ($queues) {
            $batch = [];
            foreach ($queues as $q) {
                $batch[] = [
                    'queue_id' => $q->id,
                    'name' => $q->name,
                    'channel_name' => $q->channel?->name,
                    'aht_goal' => $q->aht_goal ?? 300,
                    'is_active' => $q->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('dim_queue')->insert($batch);
        });

        Schedule::chunk(100, function ($shifts) {
            $batch = [];
            foreach ($shifts as $s) {
                $batch[] = [
                    'shift_id' => $s->id,
                    'name' => $s->name,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'total_minutes' => $s->total_minutes,
                    'lunch_minutes' => $s->lunch_minutes ?? 45,
                    'break_minutes' => $s->break_minutes ?? 15,
                    'is_active' => $s->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('dim_shift')->insert($batch);
        });

        Skill::chunk(100, function ($skills) {
            $batch = [];
            foreach ($skills as $s) {
                $batch[] = [
                    'skill_id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                    'category' => $s->category,
                    'is_active' => $s->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('dim_skill')->insert($batch);
        });
    }

    private function refreshFactCalls(CarbonInterface $start, CarbonInterface $end): void
    {
        $records = AgentCallPerformance::whereBetween('start_time', [$start, $end])
            ->with('employee.team.department')
            ->get();

        if ($records->isEmpty()) {
            return;
        }

        $intervalMinutes = 15;
        $batch = [];

        foreach ($records as $r) {
            $ts = $r->start_time;
            if (! $ts) {
                continue;
            }

            $handleSeconds = ($r->talk_time ?? 0) + ($r->hold_time ?? 0) + ($r->work_time ?? 0);
            $dateStr = $ts->toDateString();
            $minutesSinceMidnight = (int) $ts->format('H') * 60 + (int) $ts->format('i');
            $slot = (int) floor($minutesSinceMidnight / $intervalMinutes);
            $intervalId = $slot + 1;

            $employee = $r->employee;

            $batch[] = [
                'dim_date_id' => null,
                'dim_interval_id' => $intervalId,
                'dim_employee_id' => $r->employee_id,
                'dim_queue_id' => null,
                'dim_team_id' => $employee?->team_id,
                'dim_department_id' => $employee?->department_id,
                'source_call_id' => $r->id,
                'talk_seconds' => $r->talk_time ?? 0,
                'hold_seconds' => $r->hold_time ?? 0,
                'wrap_seconds' => $r->work_time ?? 0,
                'ring_seconds' => 0,
                'queue_seconds' => 0,
                'handle_seconds' => $handleSeconds,
                'is_abandoned' => false,
                'is_handled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->upsertFact('fact_calls', $batch, ['source_call_id'], ['talk_seconds', 'hold_seconds', 'wrap_seconds', 'handle_seconds']);
    }

    private function refreshFactSchedule(CarbonInterface $start, CarbonInterface $end): void
    {
        $intervalMinutes = 15;
        $intervalsPerDay = (24 * 60) / $intervalMinutes;

        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->endOfDay();

        while ($current->lte($endDay)) {
            $dateStr = $current->toDateString();
            $dayOfWeek = (int) $current->format('N');
            $weekStart = $current->copy()->startOfWeek(CarbonInterface::MONDAY);

            $weeklySchedule = WeeklySchedule::where('week_start_date', $weekStart->toDateString())
                ->where('status', 'published')
                ->first();

            if (! $weeklySchedule) {
                $current->addDay();

                continue;
            }

            $assignments = WeeklyScheduleAssignment::with('employee.team.department', 'schedule')
                ->where('weekly_schedule_id', $weeklySchedule->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_replaced', false)
                ->get();

            $batch = [];
            foreach ($assignments as $a) {
                $employee = $a->employee;
                if (! $employee) {
                    continue;
                }

                for ($slot = 0; $slot < $intervalsPerDay; $slot++) {
                    $intervalStartTime = sprintf('%02d:%02d:00', intdiv($slot * $intervalMinutes, 60), ($slot * $intervalMinutes) % 60);
                    $intervalEndTime = sprintf('%02d:%02d:00', intdiv(($slot + 1) * $intervalMinutes, 60), (($slot + 1) * $intervalMinutes) % 60);
                    $scheduledStart = $a->start_time?->format('H:i:s');
                    $scheduledEnd = $a->end_time?->format('H:i:s');

                    $isScheduled = $scheduledStart && $scheduledEnd
                        && $scheduledStart < $intervalEndTime
                        && $scheduledEnd > $intervalStartTime;
                    $intervalId = $slot + 1;

                    $batch[] = [
                        'dim_date_id' => null,
                        'dim_interval_id' => $intervalId,
                        'dim_employee_id' => $a->employee_id,
                        'dim_team_id' => $employee->team_id,
                        'dim_department_id' => $employee->department_id,
                        'dim_shift_id' => $a->schedule_id,
                        'scheduled_start' => $scheduledStart,
                        'scheduled_end' => $scheduledEnd,
                        'scheduled_minutes' => $isScheduled ? $intervalMinutes : 0,
                        'lunch_minutes' => 0,
                        'break_minutes' => 0,
                        'is_off' => ! $isScheduled,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            $this->upsertFact('fact_schedule', $batch, ['dim_date_id', 'dim_interval_id', 'dim_employee_id'], ['scheduled_minutes', 'is_off']);
            $current->addDay();
        }
    }

    private function refreshFactQuality(CarbonInterface $start, CarbonInterface $end): void
    {
        Evaluation::whereBetween('dteval', [$start->toDateString(), $end->toDateString()])
            ->with(['employee.team', 'queue'])
            ->chunk(100, function ($evaluations) {
                $batch = [];
                foreach ($evaluations as $ev) {
                    $batch[] = [
                        'dim_date_id' => null,
                        'dim_employee_id' => $ev->employee_id,
                        'dim_queue_id' => $ev->queue_id,
                        'dim_team_id' => $ev->employee?->team_id,
                        'source_evaluation_id' => $ev->id,
                        'score' => $ev->score,
                        'max_score' => $ev->max_score ?? 100,
                        'score_pct' => $ev->score && ($ev->max_score ?? 100) > 0
                            ? round(($ev->score / ($ev->max_score ?? 100)) * 100, 2) : null,
                        'has_redflag' => $ev->has_redflag ?? false,
                        'evaluator_id' => $ev->evaluator_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $this->upsertFact('fact_quality', $batch, ['source_evaluation_id'], ['score', 'score_pct']);
            });
    }

    private function refreshFactAbsence(CarbonInterface $start, CarbonInterface $end): void
    {
        $exceptions = ScheduleException::with('employee.team.department', 'reason')
            ->whereDate('start_at', '<=', $end->toDateString())
            ->whereDate('end_at', '>=', $start->toDateString())
            ->get();

        $batch = [];
        foreach ($exceptions as $ex) {
            $exStart = $ex->start_at->max($start);
            $exEnd = $ex->end_at->min($end);
            $minutes = (int) ceil($exStart->diffInMinutes($exEnd));

            $employee = $ex->employee;
            $batch[] = [
                'dim_date_id' => null,
                'dim_employee_id' => $ex->employee_id,
                'dim_team_id' => $employee?->team_id,
                'dim_department_id' => $employee?->department_id,
                'source_exception_id' => $ex->id,
                'source_leave_id' => null,
                'reason_name' => $ex->reason?->name ?? 'Unknown',
                'duration_minutes' => $minutes,
                'is_full_day' => $ex->is_full_day ?? false,
                'is_excused' => $ex->reason?->is_excused ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $leaves = LeaveRequest::with('employee.team.department')
            ->whereIn('status', ['approved', 'aprobado'])
            ->whereDate('start_time', '<=', $end->toDateString())
            ->whereDate('end_time', '>=', $start->toDateString())
            ->get();

        foreach ($leaves as $lv) {
            $lvStart = $lv->start_time->max($start);
            $lvEnd = $lv->end_time->min($end);
            $minutes = (int) ceil($lvStart->diffInMinutes($lvEnd));

            $employee = $lv->employee;
            $batch[] = [
                'dim_date_id' => null,
                'dim_employee_id' => $lv->employee_id,
                'dim_team_id' => $employee?->team_id,
                'dim_department_id' => $employee?->department_id,
                'source_exception_id' => null,
                'source_leave_id' => $lv->id,
                'reason_name' => $lv->type,
                'duration_minutes' => $minutes,
                'is_full_day' => false,
                'is_excused' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->upsertFact('fact_absence', $batch, ['source_exception_id', 'source_leave_id', 'dim_date_id'], ['duration_minutes']);
    }

    private function refreshFactAgentInterval(CarbonInterface $start, CarbonInterface $end): void
    {
        $intervalMinutes = 15;

        AgentIntervalMetric::with('employee.team.department')
            ->whereBetween('interval_start', [$start, $end])
            ->chunk(100, function ($metrics) use ($intervalMinutes) {
                $batch = [];
                foreach ($metrics as $m) {
                    $ts = $m->interval_start;
                    if (! $ts) {
                        continue;
                    }
                    $minutesSinceMidnight = (int) $ts->format('H') * 60 + (int) $ts->format('i');
                    $slot = (int) floor($minutesSinceMidnight / $intervalMinutes);
                    $intervalId = $slot + 1;

                    $employee = $m->employee;
                    $batch[] = [
                        'dim_date_id' => null,
                        'dim_interval_id' => $intervalId,
                        'dim_employee_id' => $m->employee_id,
                        'dim_team_id' => $employee?->team_id,
                        'dim_department_id' => $employee?->department_id,
                        'talk_seconds' => $m->talk_seconds,
                        'hold_seconds' => $m->hold_seconds,
                        'ready_seconds' => $m->ready_seconds,
                        'not_ready_seconds' => $m->not_ready_seconds,
                        'wrap_seconds' => $m->wrap_seconds,
                        'calls_handled' => $m->calls_handled,
                        'aht_seconds' => $m->aht_seconds,
                        'occupancy' => $m->occupancy,
                        'utilization' => $m->utilization,
                        'adherence' => $m->adherence,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $this->upsertFact('fact_agent_interval', $batch, ['dim_date_id', 'dim_interval_id', 'dim_employee_id'], ['talk_seconds', 'hold_seconds', 'calls_handled', 'occupancy']);
            });
    }

    private function upsertFact(string $table, array $rows, array $uniqueBy, array $updateFields): void
    {
        if (empty($rows)) {
            return;
        }

        foreach ($rows as &$row) {
            $row['updated_at'] = now();
        }

        DB::table($table)->upsert($rows, $uniqueBy, $updateFields);
    }
}
