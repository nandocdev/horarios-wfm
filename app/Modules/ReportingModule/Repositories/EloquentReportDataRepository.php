<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Repositories;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\ReportingModule\DTOs\AbsenteeismRowDTO;
use App\Modules\ReportingModule\DTOs\AgentPerformanceRowDTO;
use App\Modules\ReportingModule\DTOs\AhtRowDTO;
use App\Modules\ReportingModule\DTOs\AttendanceSummaryRowDTO;
use App\Modules\ReportingModule\DTOs\ExceptionSummaryRowDTO;
use App\Modules\ReportingModule\DTOs\IntradayActivityRowDTO;
use App\Modules\ReportingModule\DTOs\LeaveRowDTO;
use App\Modules\ReportingModule\DTOs\PeriodActivityRowDTO;
use App\Modules\ReportingModule\DTOs\RankingRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\TardinessRowDTO;
use App\Modules\ReportingModule\DTOs\TeamPerformanceRowDTO;
use App\Modules\ReportingModule\DTOs\VacationRowDTO;
use App\Modules\ReportingModule\DTOs\VolumeIntervalRowDTO;
use App\Modules\ReportingModule\DTOs\VolumeRowDTO;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\DailyOperatorReport;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentReportDataRepository
{
    private const int SLA_THRESHOLD_SECONDS = 20;

    private const array LEAVE_REASON_CODES = [
        'L.', 'P', 'R', 'R.P.', 'D.',
        'P.T.', 'T.C.', 'T.E.', 'S.D',
        'C.A', 'T.A', 'C.A 7', 'C.M.', 'S.C.',
    ];

    private const array LATENESS_REASON_CODES = ['T.I.', 'T.J.'];

    public function getRawAbsenteeismData(ReportFilterDTO $filters): Collection
    {
        $rows = DB::table('weekly_schedule_assignments', 'wsa')
            ->join('weekly_schedules AS ws', 'ws.id', '=', 'wsa.weekly_schedule_id')
            ->join('employees AS e', 'e.id', '=', 'wsa.employee_id')
            ->leftJoin('teams AS t', 't.id', '=', 'e.team_id')
            ->leftJoin('daily_operator_reports AS dor', function ($join): void {
                $join->on('dor.employee_id', '=', 'e.id')
                    ->whereRaw('dor.report_date = (ws.week_start_date + (wsa.day_of_week - 1)::int)')
                    ->whereNotNull('dor.real_entry');
            })
            ->leftJoin('schedule_exceptions AS se', function ($join): void {
                $join->on('se.employee_id', '=', 'e.id')
                    ->whereRaw('se.start_at::date <= (ws.week_start_date + (wsa.day_of_week - 1)::int)')
                    ->whereRaw('se.end_at::date >= (ws.week_start_date + (wsa.day_of_week - 1)::int)');
            })
            ->where('ws.status', 'published')
            ->whereRaw('(ws.week_start_date + (wsa.day_of_week - 1)::int) >= ?', [$filters->dateFrom])
            ->whereRaw('(ws.week_start_date + (wsa.day_of_week - 1)::int) <= ?', [$filters->dateTo])
            ->whereNull('dor.id')
            ->whereNull('se.id');

        if ($filters->teamId !== null) {
            $rows->where('e.team_id', $filters->teamId);
        }

        if ($filters->employeeId !== null) {
            $rows->where('e.id', $filters->employeeId);
        }

        $result = $rows
            ->select([
                'e.id as employee_id',
                DB::raw("CONCAT(e.first_name, ' ', e.last_name) as employee_name"),
                'e.employee_number',
                't.name as team_name',
                DB::raw('(ws.week_start_date + (wsa.day_of_week - 1)::int)::date as date'),
                DB::raw("'absent' as origin_type"),
                DB::raw("'Sin inicio de sesión' as cause_name"),
                DB::raw('false as is_justified'),
                DB::raw('false as is_full_day'),
                'wsa.start_time',
                'wsa.end_time',
                DB::raw('EXTRACT(EPOCH FROM wsa.end_time - wsa.start_time) / 60 as minutes_absent'),
                DB::raw('NULL::text as remarks'),
            ])
            ->orderBy('date')
            ->orderBy('employee_name')
            ->distinct()
            ->get();

        return $result->map(fn (object $row): AbsenteeismRowDTO => new AbsenteeismRowDTO(
            employeeId: (int) $row->employee_id,
            employeeName: $row->employee_name,
            employeeNumber: $row->employee_number,
            teamName: $row->team_name,
            date: $row->date,
            originType: $row->origin_type,
            causeName: $row->cause_name,
            isJustified: (bool) $row->is_justified,
            isFullDay: (bool) $row->is_full_day,
            startAt: $row->start_time ? (string) $row->start_time : null,
            endAt: $row->end_time ? (string) $row->end_time : null,
            minutesAbsent: $row->minutes_absent !== null ? (int) round((float) $row->minutes_absent) : null,
            remarks: $row->remarks,
        ));
    }

    public function getTardinessData(ReportFilterDTO $filters): Collection
    {
        $scheduleExceptions = $this->baseTardinessQuery($filters);

        $attendanceIncidents = AttendanceIncident::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'employees.employee_number',
                'teams.name as team_name',
                'attendance_incidents.incident_date as date',
                DB::raw('NULL::time as start_at'),
                'attendance_incidents.start_time as actual_start',
                DB::raw('EXTRACT(EPOCH FROM attendance_incidents.end_time - attendance_incidents.start_time) / 60 as minutes_late'),
                DB::raw('NULL::text as cause_name'),
                DB::raw("'Tardanza' as incident_type"),
                'attendance_incidents.user_comment as justification',
            ])
            ->join('employees', 'employees.id', '=', 'attendance_incidents.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->join('incident_types', 'incident_types.id', '=', 'attendance_incidents.incident_type_id')
            ->where('incident_types.code', 'LATE')
            ->whereDate('attendance_incidents.incident_date', '>=', $filters->dateFrom)
            ->whereDate('attendance_incidents.incident_date', '<=', $filters->dateTo);

        $scheduleExceptions = $this->applyBaseFilters($scheduleExceptions, $filters);
        $attendanceIncidents = $this->applyBaseFilters($attendanceIncidents, $filters);

        $union = $scheduleExceptions->unionAll($attendanceIncidents);

        $rows = $union->orderBy('date')->orderBy('employee_name')->get();

        return $rows->map(fn (object $row): TardinessRowDTO => new TardinessRowDTO(
            employeeId: (int) $row->employee_id,
            employeeName: $row->employee_name,
            employeeNumber: $row->employee_number,
            teamName: $row->team_name,
            date: $row->date,
            scheduledStart: $row->start_at !== null ? (string) $row->start_at : null,
            actualLogin: $row->actual_start !== null ? (string) $row->actual_start : null,
            minutesLate: isset($row->minutes_late) ? (int) round((float) $row->minutes_late) : (isset($row->minutes_absent) ? (int) round((float) $row->minutes_absent) : null),
            incidentType: $row->incident_type ?? $row->cause_name ?? null,
            justification: $row->justification ?? $row->remarks ?? null,
        ));
    }

    public function getLeavesData(ReportFilterDTO $filters): Collection
    {
        $query = $this->baseAbsenceQuery($filters)
            ->whereIn('absence_reason_codes.short_code', self::LEAVE_REASON_CODES);

        $query = $this->applyBaseFilters($query, $filters);

        $rows = $query->orderBy('date')->orderBy('employee_name')->get();

        return $rows->map(fn (object $row): LeaveRowDTO => new LeaveRowDTO(
            employeeId: (int) $row->employee_id,
            employeeName: $row->employee_name,
            employeeNumber: $row->employee_number,
            teamName: $row->team_name,
            date: $row->date,
            leaveType: $row->cause_name,
            isExcused: true,
            status: 'approved',
            minutes: $row->minutes_absent !== null ? (int) round((float) $row->minutes_absent) : null,
            remarks: $row->remarks,
        ));
    }

    public function getVacationsData(ReportFilterDTO $filters): Collection
    {
        $rows = ScheduleException::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'employees.employee_number',
                'teams.name as team_name',
                DB::raw('schedule_exceptions.start_at::date as start_date'),
                DB::raw('schedule_exceptions.end_at::date as end_date'),
                DB::raw('(schedule_exceptions.end_at::date - schedule_exceptions.start_at::date) + 1 as days_taken'),
                'schedule_exceptions.remarks',
            ])
            ->join('employees', 'employees.id', '=', 'schedule_exceptions.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->join('absence_reason_codes', 'absence_reason_codes.id', '=', 'schedule_exceptions.absence_reason_code_id')
            ->where('absence_reason_codes.short_code', 'V.')
            ->whereDate('schedule_exceptions.start_at', '<=', $filters->dateTo)
            ->whereDate('schedule_exceptions.end_at', '>=', $filters->dateFrom);

        $rows = $this->applyBaseFilters($rows, $filters);

        $result = $rows->orderBy('start_date')->orderBy('employee_name')->get();

        return $result->map(fn (object $row): VacationRowDTO => new VacationRowDTO(
            employeeId: (int) $row->employee_id,
            employeeName: $row->employee_name,
            employeeNumber: $row->employee_number,
            teamName: $row->team_name,
            startDate: $row->start_date,
            endDate: $row->end_date,
            daysTaken: max(1, (int) $row->days_taken),
            remarks: $row->remarks,
        ));
    }

    public function getAttendanceSummaryData(ReportFilterDTO $filters): Collection
    {
        $dateDiff = (new \DateTime($filters->dateTo))->diff(new \DateTime($filters->dateFrom))->days + 1;

        $employees = Employee::query()
            ->select(['id', DB::raw("CONCAT(first_name, ' ', last_name) as name"), 'team_id'])
            ->when($filters->teamId, fn (Builder $q) => $q->where('team_id', $filters->teamId))
            ->when($filters->employeeId, fn (Builder $q) => $q->where('id', $filters->employeeId))
            ->with('team:id,name')
            ->get();

        $absences = $this->countByEmployee('schedule_exceptions', 'employee_id', $filters, fn ($q) => $q->whereIn('absence_reason_code_id', AbsenceReasonCode::where('is_excused', false)->pluck('id')));
        $leaves = $this->countByEmployee('schedule_exceptions', 'employee_id', $filters, fn ($q) => $q->whereIn('absence_reason_code_id', AbsenceReasonCode::whereIn('short_code', self::LEAVE_REASON_CODES)->pluck('id')));
        $vacations = $this->countByEmployee('schedule_exceptions', 'employee_id', $filters, fn ($q) => $q->whereIn('absence_reason_code_id', AbsenceReasonCode::where('short_code', 'V.')->pluck('id')));
        $tardiness = $this->countByEmployee('attendance_incidents', 'employee_id', $filters, fn ($q) => $q->whereIn('incident_type_id', function ($q) {
            $q->select('id')->from('incident_types')->where('code', 'LATE');
        }));

        return $employees->map(fn (Employee $e) => new AttendanceSummaryRowDTO(
            entityName: $e->name,
            entityType: 'employee',
            totalScheduledDays: $dateDiff,
            totalAbsences: $absences[$e->id] ?? 0,
            totalTardiness: $tardiness[$e->id] ?? 0,
            totalLeaves: $leaves[$e->id] ?? 0,
            totalVacationDays: $vacations[$e->id] ?? 0,
            attendanceRate: $dateDiff > 0 ? round((($dateDiff - ($absences[$e->id] ?? 0)) / $dateDiff) * 100, 1) : 0,
            tardinessRate: $dateDiff > 0 ? round((($tardiness[$e->id] ?? 0) / $dateDiff) * 100, 1) : 0,
        ));
    }

    public function getExceptionSummaryData(ReportFilterDTO $filters): Collection
    {
        $query = AbsenceReasonCode::query()
            ->select([
                'absence_reason_codes.name',
                'absence_reason_codes.short_code',
                'absence_reason_codes.is_excused',
                DB::raw('COUNT(schedule_exceptions.id) as total_occurrences'),
                DB::raw('COALESCE(SUM(EXTRACT(EPOCH FROM schedule_exceptions.end_at - schedule_exceptions.start_at) / 60), 0) as total_minutes_lost'),
                DB::raw('COUNT(DISTINCT schedule_exceptions.employee_id) as employees_affected'),
            ])
            ->leftJoin('schedule_exceptions', function ($join) use ($filters) {
                $join->on('absence_reason_codes.id', '=', 'schedule_exceptions.absence_reason_code_id')
                    ->whereDate('schedule_exceptions.start_at', '<=', $filters->dateTo)
                    ->whereDate('schedule_exceptions.end_at', '>=', $filters->dateFrom);
            })
            ->groupBy('absence_reason_codes.id', 'absence_reason_codes.name', 'absence_reason_codes.short_code', 'absence_reason_codes.is_excused')
            ->orderByDesc('total_occurrences');

        if ($filters->teamId !== null) {
            $query->whereHas('scheduleExceptions.employee', fn (Builder $q) => $q->where('team_id', $filters->teamId));
        }

        return $query->get()->map(fn (object $row): ExceptionSummaryRowDTO => new ExceptionSummaryRowDTO(
            causeName: $row->name,
            shortCode: $row->short_code,
            isExcused: (bool) $row->is_excused,
            totalOccurrences: (int) $row->total_occurrences,
            totalMinutesLost: (int) round((float) $row->total_minutes_lost),
            employeesAffected: (int) $row->employees_affected,
        ));
    }

    public function getIntradayActivitiesData(ReportFilterDTO $filters): Collection
    {
        $query = IntradayActivity::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                DB::raw('activity_types.name as activity_name'),
                'activity_types.is_productive',
                DB::raw('LOWER(intraday_activities.time_range::text) as time_range'),
                'intraday_activities.notes',
            ])
            ->join('employees', 'employees.id', '=', 'intraday_activities.employee_id')
            ->join('activity_types', 'activity_types.id', '=', 'intraday_activities.activity_type_id')
            ->whereRaw('intraday_activities.time_range && TSTZRANGE(?::timestamp, ?::timestamp)', [$filters->dateFrom, $filters->dateTo.' 23:59:59']);

        if ($filters->employeeId !== null) {
            $query->where('intraday_activities.employee_id', $filters->employeeId);
        }

        if ($filters->teamId !== null) {
            $query->whereHas('employee', fn (Builder $q) => $q->where('team_id', $filters->teamId));
        }

        return $query->orderBy('employee_name')->get()->map(fn (object $row): IntradayActivityRowDTO => new IntradayActivityRowDTO(
            employeeId: (int) $row->employee_id,
            employeeName: $row->employee_name,
            date: $filters->dateFrom,
            startTime: $this->parseRangeStart($row->time_range),
            endTime: $this->parseRangeEnd($row->time_range),
            activityName: $row->activity_name,
            isProductive: (bool) $row->is_productive,
            notes: $row->notes,
        ));
    }

    public function getPeriodActivitiesData(ReportFilterDTO $filters): Collection
    {
        $query = IntradayActivity::query()
            ->select([
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as entity_name"),
                DB::raw("'employee' as entity_type"),
                'activity_types.name as activity_name',
                'activity_types.is_productive',
                DB::raw('SUM(EXTRACT(EPOCH FROM UPPER(intraday_activities.time_range) - LOWER(intraday_activities.time_range))) / 60 as total_minutes'),
            ])
            ->join('employees', 'employees.id', '=', 'intraday_activities.employee_id')
            ->join('activity_types', 'activity_types.id', '=', 'intraday_activities.activity_type_id')
            ->whereRaw('intraday_activities.time_range && TSTZRANGE(?::timestamp, ?::timestamp)', [$filters->dateFrom, $filters->dateTo.' 23:59:59'])
            ->groupBy('employees.id', 'activity_types.id', 'activity_types.name', 'activity_types.is_productive')
            ->orderByDesc('total_minutes');

        if ($filters->employeeId !== null) {
            $query->where('intraday_activities.employee_id', $filters->employeeId);
        }

        if ($filters->teamId !== null) {
            $query->whereHas('employee', fn (Builder $q) => $q->where('team_id', $filters->teamId));
        }

        return $query->get()->map(fn (object $row): PeriodActivityRowDTO => new PeriodActivityRowDTO(
            entityName: $row->entity_name,
            entityType: $row->entity_type,
            activityName: $row->activity_name,
            totalMinutes: (int) round((float) $row->total_minutes),
            isProductive: (bool) $row->is_productive,
            compliancePct: null,
        ));
    }

    public function getAhtDetailData(ReportFilterDTO $filters): Collection
    {
        $query = AgentCallPerformance::query()
            ->select([
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as agent_name"),
                'agent_call_performance.csq_name as queue_name',
                DB::raw('agent_call_performance.start_time::date as date'),
                DB::raw('COUNT(*) as calls_handled'),
                DB::raw('AVG(agent_call_performance.talk_time) as avg_talk_time'),
                DB::raw('AVG(agent_call_performance.work_time) as avg_work_time'),
                DB::raw('AVG(agent_call_performance.hold_time) as avg_hold_time'),
                DB::raw('AVG(agent_call_performance.talk_time + agent_call_performance.work_time) as aht'),
                DB::raw('MIN(agent_call_performance.talk_time + agent_call_performance.work_time) as min_aht'),
                DB::raw('MAX(agent_call_performance.talk_time + agent_call_performance.work_time) as max_aht'),
                'call_queues.aht_goal',
            ])
            ->leftJoin('employees', 'employees.id', '=', 'agent_call_performance.employee_id')
            ->leftJoin('call_queues', 'call_queues.name', '=', 'agent_call_performance.csq_name')
            ->whereDate('agent_call_performance.start_time', '>=', $filters->dateFrom)
            ->whereDate('agent_call_performance.start_time', '<=', $filters->dateTo)
            ->whereNotNull('agent_call_performance.employee_id')
            ->groupBy(
                'employees.id',
                'agent_call_performance.csq_name',
                DB::raw('agent_call_performance.start_time::date'),
                'call_queues.aht_goal',
            )
            ->orderBy('date')
            ->orderBy('queue_name');

        if ($filters->queueId !== null) {
            $query->whereIn('agent_call_performance.csq_name', function ($q) use ($filters) {
                $q->select('name')->from('call_queues')->where('id', $filters->queueId);
            });
        }

        if ($filters->employeeId !== null) {
            $query->where('agent_call_performance.employee_id', $filters->employeeId);
        }

        if ($filters->teamId !== null) {
            $query->whereHas('employee', fn (Builder $q) => $q->where('team_id', $filters->teamId));
        }

        return $query->get()->map(fn (object $row): AhtRowDTO => new AhtRowDTO(
            agentName: $row->agent_name,
            queueName: $row->queue_name,
            date: $row->date,
            callsHandled: (int) $row->calls_handled,
            avgTalkTime: (float) ($row->avg_talk_time ?? 0),
            avgWorkTime: (float) ($row->avg_work_time ?? 0),
            avgHoldTime: (float) ($row->avg_hold_time ?? 0),
            aht: (float) ($row->aht ?? 0),
            ahtGoal: $row->aht_goal !== null ? (int) $row->aht_goal : null,
            deviation: $row->aht !== null && $row->aht_goal !== null ? (float) ($row->aht - $row->aht_goal) : null,
            minAht: $row->min_aht !== null ? (float) $row->min_aht : null,
            maxAht: $row->max_aht !== null ? (float) $row->max_aht : null,
        ));
    }

    public function getAhtSummaryData(ReportFilterDTO $filters): Collection
    {
        $query = AgentCallPerformance::query()
            ->select([
                'agent_call_performance.csq_name as queue_name',
                DB::raw('agent_call_performance.start_time::date as date'),
                DB::raw('COUNT(*) as calls_handled'),
                DB::raw('AVG(agent_call_performance.talk_time + agent_call_performance.work_time) as aht'),
                DB::raw('AVG(agent_call_performance.talk_time) as avg_talk_time'),
                DB::raw('AVG(agent_call_performance.work_time) as avg_work_time'),
                DB::raw('AVG(agent_call_performance.hold_time) as avg_hold_time'),
                'call_queues.aht_goal',
            ])
            ->leftJoin('call_queues', 'call_queues.name', '=', 'agent_call_performance.csq_name')
            ->whereDate('agent_call_performance.start_time', '>=', $filters->dateFrom)
            ->whereDate('agent_call_performance.start_time', '<=', $filters->dateTo)
            ->groupBy(
                'agent_call_performance.csq_name',
                DB::raw('agent_call_performance.start_time::date'),
                'call_queues.aht_goal',
            )
            ->orderBy('date')
            ->orderBy('queue_name');

        if ($filters->queueId !== null) {
            $query->whereIn('agent_call_performance.csq_name', function ($q) use ($filters) {
                $q->select('name')->from('call_queues')->where('id', $filters->queueId);
            });
        }

        return $query->get()->map(fn (object $row): AhtRowDTO => new AhtRowDTO(
            agentName: '—',
            queueName: $row->queue_name,
            date: $row->date,
            callsHandled: (int) $row->calls_handled,
            avgTalkTime: (float) ($row->avg_talk_time ?? 0),
            avgWorkTime: (float) ($row->avg_work_time ?? 0),
            avgHoldTime: (float) ($row->avg_hold_time ?? 0),
            aht: (float) ($row->aht ?? 0),
            ahtGoal: $row->aht_goal !== null ? (int) $row->aht_goal : null,
            deviation: $row->aht !== null && $row->aht_goal !== null ? (float) ($row->aht - $row->aht_goal) : null,
        ));
    }

    public function getVolumeDetailData(ReportFilterDTO $filters): Collection
    {
        $query = CallRecord::query()
            ->select([
                'call_queues.name as queue_name',
                DB::raw('call_records.ivr_started_at::date as date'),
                DB::raw('COUNT(*) as received'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition = 2) as handled'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition IN (1, 4, 13)) as abandoned'),
                DB::raw('AVG(call_records.talk_time + call_records.work_time) FILTER (WHERE call_records.contact_disposition = 2) as aht'),
                DB::raw('AVG(call_records.queue_time) FILTER (WHERE call_records.contact_disposition = 2) as asa'),
                DB::raw('MAX(call_records.queue_time) as max_wait_time'),
                DB::raw('MIN(call_records.queue_time) as min_wait_time'),
                DB::raw('AVG(call_records.queue_time) FILTER (WHERE call_records.contact_disposition IN (1, 4, 13)) as avg_abandon_time'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition = 2 AND call_records.queue_time <= 20) as sl_count'),
            ])
            ->join('call_queues', 'call_queues.id', '=', 'call_records.queue_id')
            ->whereDate('call_records.ivr_started_at', '>=', $filters->dateFrom)
            ->whereDate('call_records.ivr_started_at', '<=', $filters->dateTo)
            ->groupBy('call_queues.name', DB::raw('call_records.ivr_started_at::date'))
            ->orderBy('date')
            ->orderBy('queue_name');

        if ($filters->queueId !== null) {
            $query->where('call_records.queue_id', $filters->queueId);
        }

        return $query->get()->map(fn (object $row): VolumeRowDTO => new VolumeRowDTO(
            queueName: $row->queue_name,
            date: $row->date,
            received: (int) $row->received,
            handled: (int) $row->handled,
            abandoned: (int) $row->abandoned,
            abandonmentRate: ServiceQualityMetrics::abandonmentRate((int) $row->abandoned, (int) $row->received),
            aht: $row->aht !== null ? (float) $row->aht : null,
            asa: $row->asa !== null ? (float) $row->asa : null,
            maxWaitTime: $row->max_wait_time !== null ? (int) $row->max_wait_time : null,
            minWaitTime: $row->min_wait_time !== null ? (int) $row->min_wait_time : null,
            avgAbandonTime: $row->avg_abandon_time !== null ? (float) $row->avg_abandon_time : null,
            slaPercentage: ServiceQualityMetrics::serviceLevel((int) $row->sl_count, (int) $row->received),
        ));
    }

    public function getVolumeByIntervalData(ReportFilterDTO $filters): Collection
    {
        $intervalMinutes = match ($filters->interval) {
            'weekly' => 10080,
            'monthly' => 43200,
            default => 30,
        };

        $query = CallRecord::query()
            ->select([
                'call_queues.name as queue_name',
                DB::raw("to_char(date_trunc('hour', call_records.ivr_started_at) + (EXTRACT(MINUTE FROM call_records.ivr_started_at)::int / {$intervalMinutes}) * interval '{$intervalMinutes} minutes', 'YYYY-MM-DD HH24:MI') as interval_label"),
                DB::raw('COUNT(*) as offered'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition = 2) as handled'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition IN (1, 4, 13)) as abandoned'),
                DB::raw('AVG(call_records.talk_time + call_records.work_time) FILTER (WHERE call_records.contact_disposition = 2) as aht'),
                DB::raw('AVG(call_records.queue_time) FILTER (WHERE call_records.contact_disposition = 2) as asa'),
                DB::raw('MAX(call_records.queue_time) as max_wait_time'),
            ])
            ->join('call_queues', 'call_queues.id', '=', 'call_records.queue_id')
            ->whereDate('call_records.ivr_started_at', '>=', $filters->dateFrom)
            ->whereDate('call_records.ivr_started_at', '<=', $filters->dateTo)
            ->groupBy('call_queues.name', 'interval_label')
            ->orderBy('queue_name')
            ->orderBy('interval_label');

        if ($filters->queueId !== null) {
            $query->where('call_records.queue_id', $filters->queueId);
        }

        return $query->get()->map(fn (object $row): VolumeIntervalRowDTO => new VolumeIntervalRowDTO(
            queueName: $row->queue_name,
            interval: $row->interval_label,
            offered: (int) $row->offered,
            handled: (int) $row->handled,
            abandoned: (int) $row->abandoned,
            abandonmentRate: ServiceQualityMetrics::abandonmentRate((int) $row->abandoned, (int) $row->offered),
            aht: $row->aht !== null ? (float) $row->aht : null,
            asa: $row->asa !== null ? (float) $row->asa : null,
            maxWaitTime: $row->max_wait_time !== null ? (int) $row->max_wait_time : null,
        ));
    }

    public function getVolumeSummaryData(ReportFilterDTO $filters): Collection
    {
        $query = CallRecord::query()
            ->select([
                'call_queues.name as queue_name',
                DB::raw('COUNT(*) as received'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition = 2) as handled'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition IN (1, 4, 13)) as abandoned'),
                DB::raw('AVG(call_records.talk_time + call_records.work_time) FILTER (WHERE call_records.contact_disposition = 2) as aht'),
                DB::raw('AVG(call_records.queue_time) FILTER (WHERE call_records.contact_disposition = 2) as asa'),
            ])
            ->join('call_queues', 'call_queues.id', '=', 'call_records.queue_id')
            ->whereDate('call_records.ivr_started_at', '>=', $filters->dateFrom)
            ->whereDate('call_records.ivr_started_at', '<=', $filters->dateTo)
            ->groupBy('call_queues.name')
            ->orderBy('queue_name');

        if ($filters->queueId !== null) {
            $query->where('call_records.queue_id', $filters->queueId);
        }

        return $query->get()->map(fn (object $row): VolumeRowDTO => new VolumeRowDTO(
            queueName: $row->queue_name,
            date: $filters->interval,
            received: (int) $row->received,
            handled: (int) $row->handled,
            abandoned: (int) $row->abandoned,
            abandonmentRate: ServiceQualityMetrics::abandonmentRate((int) $row->abandoned, (int) $row->received),
            aht: $row->aht !== null ? (float) $row->aht : null,
            asa: $row->asa !== null ? (float) $row->asa : null,
        ));
    }

    public function getAgentPerformanceData(ReportFilterDTO $filters): Collection
    {
        $query = DailyOperatorReport::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'employees.employee_number',
                'teams.name as team_name',
                DB::raw('COALESCE(SUM(daily_operator_reports.handled_calls), 0) as calls_handled'),
                DB::raw('COALESCE(AVG(daily_operator_reports.avg_handle_time), 0) as aht'),
                DB::raw('COALESCE(AVG(daily_operator_reports.occupancy_pct), 0) as occupancy'),
                DB::raw('COALESCE(SUM(daily_operator_reports.talk_seconds), 0) as talk_time'),
                DB::raw('COALESCE(SUM(daily_operator_reports.ready_seconds), 0) as ready_time'),
                DB::raw('COALESCE(SUM(daily_operator_reports.acw_seconds), 0) as acw_time'),
            ])
            ->join('employees', 'employees.id', '=', 'daily_operator_reports.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->whereDate('daily_operator_reports.report_date', '>=', $filters->dateFrom)
            ->whereDate('daily_operator_reports.report_date', '<=', $filters->dateTo)
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name', 'employees.employee_number', 'teams.name')
            ->orderByDesc('calls_handled');

        if ($filters->employeeId !== null) {
            $query->where('daily_operator_reports.employee_id', $filters->employeeId);
        }

        if ($filters->teamId !== null) {
            $query->where('employees.team_id', $filters->teamId);
        }

        return $query->get()->map(fn (object $row): AgentPerformanceRowDTO => new AgentPerformanceRowDTO(
            employeeId: (int) $row->employee_id,
            employeeName: $row->employee_name,
            employeeNumber: $row->employee_number,
            teamName: $row->team_name,
            callsHandled: (int) $row->calls_handled,
            aht: (float) ($row->aht ?? 0),
            occupancy: (float) ($row->occupancy ?? 0),
            talkTime: (float) ($row->talk_time ?? 0),
            readyTime: (float) ($row->ready_time ?? 0),
            acwTime: (float) ($row->acw_time ?? 0),
        ));
    }

    public function getTeamPerformanceData(ReportFilterDTO $filters): Collection
    {
        $query = DailyOperatorReport::query()
            ->select([
                'teams.name as team_name',
                DB::raw('COUNT(DISTINCT daily_operator_reports.employee_id) as agent_count'),
                DB::raw('COALESCE(SUM(daily_operator_reports.handled_calls), 0) as total_calls'),
                DB::raw('COALESCE(AVG(daily_operator_reports.avg_handle_time), 0) as avg_aht'),
                DB::raw('COALESCE(AVG(daily_operator_reports.occupancy_pct), 0) as avg_occupancy'),
                DB::raw('COALESCE(AVG(daily_operator_reports.adherence_pct), 0) as avg_adherence'),
            ])
            ->join('employees', 'employees.id', '=', 'daily_operator_reports.employee_id')
            ->join('teams', 'teams.id', '=', 'employees.team_id')
            ->whereDate('daily_operator_reports.report_date', '>=', $filters->dateFrom)
            ->whereDate('daily_operator_reports.report_date', '<=', $filters->dateTo)
            ->groupBy('teams.id', 'teams.name')
            ->orderByDesc('total_calls');

        if ($filters->teamId !== null) {
            $query->where('employees.team_id', $filters->teamId);
        }

        return $query->get()->map(fn (object $row): TeamPerformanceRowDTO => new TeamPerformanceRowDTO(
            teamName: $row->team_name,
            agentCount: (int) $row->agent_count,
            totalCalls: (int) $row->total_calls,
            avgAht: (float) ($row->avg_aht ?? 0),
            avgOccupancy: (float) ($row->avg_occupancy ?? 0),
            avgAdherence: (float) ($row->avg_adherence ?? 0),
        ));
    }

    public function getRankingData(ReportFilterDTO $filters): Collection
    {
        $query = DailyOperatorReport::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'employees.employee_number',
                'teams.name as team_name',
                DB::raw('COALESCE(SUM(daily_operator_reports.handled_calls), 0) as calls_handled'),
                DB::raw('COALESCE(AVG(daily_operator_reports.avg_handle_time), 0) as aht'),
                DB::raw('COALESCE(AVG(daily_operator_reports.occupancy_pct), 0) as occupancy'),
                DB::raw('COALESCE(AVG(daily_operator_reports.adherence_pct), 0) as adherence'),
            ])
            ->join('employees', 'employees.id', '=', 'daily_operator_reports.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->whereDate('daily_operator_reports.report_date', '>=', $filters->dateFrom)
            ->whereDate('daily_operator_reports.report_date', '<=', $filters->dateTo)
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name', 'employees.employee_number', 'teams.name');

        if ($filters->teamId !== null) {
            $query->where('employees.team_id', $filters->teamId);
        }

        $rows = $query->get();

        $maxCalls = $rows->max('calls_handled') ?: 1;
        $minAht = $rows->min('aht') ?: 1;
        $maxAht = $rows->max('aht') ?: 1;
        $ahtRange = $maxAht - $minAht > 0 ? $maxAht - $minAht : 1;

        $ranked = $rows->map(function (object $row) use ($maxCalls, $minAht, $ahtRange): object {
            $callsScore = ((int) $row->calls_handled / $maxCalls) * 50;
            $ahtScore = (1 - (((float) ($row->aht ?? 0) - $minAht) / $ahtRange)) * 30;
            $occScore = ((float) ($row->occupancy ?? 0) / 100) * 20;

            $row->score = round($callsScore + $ahtScore + $occScore, 2);

            return $row;
        })->sortByDesc('score')->values();

        $position = 0;

        return $ranked->map(function (object $row) use (&$position): RankingRowDTO {
            $position++;

            return new RankingRowDTO(
                position: $position,
                employeeId: (int) $row->employee_id,
                employeeName: $row->employee_name,
                employeeNumber: $row->employee_number,
                teamName: $row->team_name,
                callsHandled: (int) $row->calls_handled,
                aht: (float) ($row->aht ?? 0),
                occupancy: (float) ($row->occupancy ?? 0),
                adherence: (float) ($row->adherence ?? 0),
                score: (float) $row->score,
            );
        });
    }

    private function baseAbsenceQuery(ReportFilterDTO $filters): Builder
    {
        return ScheduleException::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'employees.employee_number',
                'teams.name as team_name',
                DB::raw('schedule_exceptions.start_at::date as date'),
                DB::raw("'schedule_exception' as origin_type"),
                'absence_reason_codes.name as cause_name',
                'absence_reason_codes.is_excused as is_justified',
                'schedule_exceptions.is_full_day',
                DB::raw('schedule_exceptions.start_at::time as start_at'),
                DB::raw('schedule_exceptions.end_at::time as end_at'),
                DB::raw('EXTRACT(EPOCH FROM schedule_exceptions.end_at - schedule_exceptions.start_at) / 60 as minutes_absent'),
                'schedule_exceptions.remarks',
            ])
            ->join('employees', 'employees.id', '=', 'schedule_exceptions.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->join('absence_reason_codes', 'absence_reason_codes.id', '=', 'schedule_exceptions.absence_reason_code_id')
            ->whereDate('schedule_exceptions.start_at', '<=', $filters->dateTo)
            ->whereDate('schedule_exceptions.end_at', '>=', $filters->dateFrom);
    }

    private function baseTardinessQuery(ReportFilterDTO $filters): Builder
    {
        return ScheduleException::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'employees.employee_number',
                'teams.name as team_name',
                DB::raw('schedule_exceptions.start_at::date as date'),
                DB::raw('schedule_exceptions.start_at::time as start_at'),
                DB::raw('NULL::time as actual_start'),
                DB::raw('EXTRACT(EPOCH FROM schedule_exceptions.end_at - schedule_exceptions.start_at) / 60 as minutes_late'),
                DB::raw('absence_reason_codes.name as cause_name'),
                DB::raw("'Tardanza' as incident_type"),
                DB::raw('schedule_exceptions.remarks as justification'),
            ])
            ->join('employees', 'employees.id', '=', 'schedule_exceptions.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->join('absence_reason_codes', 'absence_reason_codes.id', '=', 'schedule_exceptions.absence_reason_code_id')
            ->whereIn('absence_reason_codes.short_code', self::LATENESS_REASON_CODES)
            ->whereDate('schedule_exceptions.start_at', '<=', $filters->dateTo)
            ->whereDate('schedule_exceptions.end_at', '>=', $filters->dateFrom);
    }

    private function applyBaseFilters(Builder $query, ReportFilterDTO $filters): Builder
    {
        if ($filters->teamId !== null) {
            $query->where('employees.team_id', $filters->teamId);
        }

        if ($filters->employeeId !== null) {
            $query->where('employees.id', $filters->employeeId);
        }

        return $query;
    }

    private function unexcusedCodes(): array
    {
        return AbsenceReasonCode::where('is_excused', false)->pluck('short_code')->toArray();
    }

    private function countByEmployee(string $table, string $fk, ReportFilterDTO $filters, callable $extraWhere): array
    {
        $query = DB::table($table)
            ->select($fk, DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$filters->dateFrom, $filters->dateTo.' 23:59:59']);

        $extraWhere($query);

        return $query->groupBy($fk)->pluck('total', $fk)->map(fn ($v) => (int) $v)->toArray();
    }

    private function parseRangeStart(string $range): string
    {
        preg_match('/^"(.+?),/', $range, $m);

        return $m[1] ?? $range;
    }

    private function parseRangeEnd(string $range): string
    {
        preg_match('/,(.+?)"\)$/', $range, $m);

        return $m[1] ?? $range;
    }
}
