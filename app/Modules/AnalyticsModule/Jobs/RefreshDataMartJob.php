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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RefreshDataMartJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(
        private readonly ?CarbonInterface $startDate = null,
        private readonly ?CarbonInterface $endDate = null,
    ) {
        $this->onQueue('wfm-heavy');
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping('refresh_datamart')];
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
        // TRUNCATE con CASCADE para evitar FK locks y en transacción
        // Si hay jobs concurrentes, WithoutOverlapping evita doble ejecución
        DB::transaction(function (): void {
            DB::statement('TRUNCATE TABLE dim_employee, dim_team, dim_department, dim_queue, dim_shift, dim_skill CASCADE');
        });

        Employee::with(['team', 'department', 'position', 'manager'])->chunk(100, function ($employees): void {
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
            if ($batch !== []) {
                DB::table('dim_employee')->upsert($batch, ['employee_id'], ['employee_number', 'full_name', 'email', 'team_id', 'team_name', 'department_id', 'department_name', 'position_id', 'position_name', 'supervisor_id', 'supervisor_name', 'hire_date', 'is_active', 'is_manager', 'updated_at']);
            }
        });

        Team::with('supervisor')->chunk(100, function ($teams): void {
            $batch = [];
            foreach ($teams as $t) {
                $batch[] = [
                    'team_id' => $t->id,
                    'name' => $t->name,
                    'supervisor_id' => $t->supervisor_id,
                    'supervisor_name' => $t->supervisor?->name,
                    'is_active' => $t->is_active ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($batch !== []) {
                DB::table('dim_team')->upsert($batch, ['team_id'], ['name', 'supervisor_id', 'supervisor_name', 'is_active', 'updated_at']);
            }
        });

        Department::with('directorate')->chunk(100, function ($departments): void {
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
            if ($batch !== []) {
                DB::table('dim_department')->upsert($batch, ['department_id'], ['name', 'directorate_id', 'directorate_name', 'is_active', 'updated_at']);
            }
        });

        CallQueue::with('channel')->chunk(100, function ($queues): void {
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
            if ($batch !== []) {
                DB::table('dim_queue')->upsert($batch, ['queue_id'], ['name', 'channel_name', 'aht_goal', 'is_active', 'updated_at']);
            }
        });

        Schedule::chunk(100, function ($shifts): void {
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
            if ($batch !== []) {
                DB::table('dim_shift')->upsert($batch, ['shift_id'], ['name', 'start_time', 'end_time', 'total_minutes', 'lunch_minutes', 'break_minutes', 'is_active', 'updated_at']);
            }
        });

        Skill::chunk(100, function ($skills): void {
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
            if ($batch !== []) {
                DB::table('dim_skill')->upsert($batch, ['skill_id'], ['name', 'code', 'category', 'is_active', 'updated_at']);
            }
        });
    }

    private function refreshFactCalls(CarbonInterface $start, CarbonInterface $end): void
    {
        $records = AgentCallPerformance::whereBetween('start_time', [$start, $end])
            ->with(['employee.team', 'employee.department'])
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
                'dim_date_id' => (int) str_replace('-', '', $dateStr),
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
                $current = $current->addDay();

                continue;
            }

            $assignments = WeeklyScheduleAssignment::with(['employee.team', 'employee.department', 'schedule'])
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
                        'dim_date_id' => (int) str_replace('-', '', $dateStr),
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
            $current = $current->addDay();
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
                        'dim_date_id' => (int) str_replace('-', '', $ev->dteval?->toDateString() ?? $ev->created_at?->toDateString() ?? now()->toDateString()),
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
        $exceptions = ScheduleException::with(['employee.team', 'employee.department', 'reason'])
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
                'dim_date_id' => (int) str_replace('-', '', $ex->start_at?->toDateString() ?? $start->toDateString()),
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

        $leaves = LeaveRequest::with(['employee.team', 'employee.department'])
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
                'dim_date_id' => (int) str_replace('-', '', $lv->start_time?->toDateString() ?? $start->toDateString()),
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

        AgentIntervalMetric::with(['employee.team', 'employee.department'])
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
                        'dim_date_id' => (int) str_replace('-', '', $ts->toDateString()),
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

        try {
            DB::table($table)->upsert($rows, $uniqueBy, $updateFields);
        } catch (QueryException $e) {
            // 42P10 = no unique constraint matching ON CONFLICT — facts DW no tienen unique en DDL actual
            // Fallback idempotente: borra por source_* y reinserta
            if ($e->getCode() === '42P10' || str_contains($e->getMessage(), 'ON CONFLICT')) {
                Log::warning("upsertFact fallback insert para {$table} (sin unique {$e->getMessage()}) — usando delete+insert");
                $this->fallbackInsert($table, $rows, $uniqueBy);
            } else {
                throw $e;
            }
        }
    }

    private function fallbackInsert(string $table, array $rows, array $uniqueBy): void
    {
        // Para fact_* el campo source_* es el identificador idempotente
        $sourceField = collect($uniqueBy)->first(fn (string $c) => str_starts_with($c, 'source_'));

        if ($sourceField !== null) {
            $ids = collect($rows)->pluck($sourceField)->filter()->unique()->values()->all();
            if ($ids !== []) {
                DB::table($table)->whereIn($sourceField, $ids)->delete();
            }
        } else {
            // Para facts sin source (schedule, interval) borra por ventana dim_date_id/interval
            // fallback simple: borra por dim_employee_id en rango
            $employeeIds = collect($rows)->pluck('dim_employee_id')->filter()->unique()->values()->all();
            if ($employeeIds !== []) {
                // No borramos todo para no perder histórico fuera de rango; insertamos con ignore de duplicados
                // Usa insertOrIgnore como último recurso
                DB::table($table)->insertOrIgnore($rows);

                return;
            }
        }

        DB::table($table)->insert($rows);
    }
}
