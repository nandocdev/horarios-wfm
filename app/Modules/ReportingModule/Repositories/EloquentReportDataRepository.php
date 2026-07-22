<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Repositories;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\ReportingModule\DTOs\AbsenteeismRowDTO;
use App\Modules\ReportingModule\DTOs\AhtRowDTO;
use App\Modules\ReportingModule\DTOs\ExceptionSummaryRowDTO;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\DTOs\VolumeRowDTO;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ScheduleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentReportDataRepository
{
    private const int SLA_THRESHOLD_SECONDS = 20;

    public function getRawAbsenteeismData(ReportFilterDTO $filters): Collection
    {
        $scheduleExceptions = ScheduleException::query()
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
                'schedule_exceptions.start_at',
                'schedule_exceptions.end_at',
                DB::raw('EXTRACT(EPOCH FROM schedule_exceptions.end_at - schedule_exceptions.start_at) / 60 as minutes_absent'),
                'schedule_exceptions.remarks',
            ])
            ->join('employees', 'employees.id', '=', 'schedule_exceptions.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->join('absence_reason_codes', 'absence_reason_codes.id', '=', 'schedule_exceptions.absence_reason_code_id')
            ->whereDate('schedule_exceptions.start_at', '<=', $filters->dateTo)
            ->whereDate('schedule_exceptions.end_at', '>=', $filters->dateFrom);

        $attendanceIncidents = AttendanceIncident::query()
            ->select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'employees.employee_number',
                'teams.name as team_name',
                'attendance_incidents.incident_date as date',
                DB::raw("'attendance_incident' as origin_type"),
                'incident_types.name as cause_name',
                DB::raw('false as is_justified'),
                DB::raw('false as is_full_day'),
                'attendance_incidents.start_time',
                'attendance_incidents.end_time',
                DB::raw('EXTRACT(EPOCH FROM attendance_incidents.end_time - attendance_incidents.start_time) / 60 as minutes_absent'),
                'attendance_incidents.user_comment as remarks',
            ])
            ->join('employees', 'employees.id', '=', 'attendance_incidents.employee_id')
            ->leftJoin('teams', 'teams.id', '=', 'employees.team_id')
            ->join('incident_types', 'incident_types.id', '=', 'attendance_incidents.incident_type_id')
            ->whereDate('attendance_incidents.incident_date', '>=', $filters->dateFrom)
            ->whereDate('attendance_incidents.incident_date', '<=', $filters->dateTo);

        $scheduleExceptions = $this->applyAbsenteeismFilters($scheduleExceptions, $filters);
        $attendanceIncidents = $this->applyAbsenteeismFilters($attendanceIncidents, $filters);

        $union = $scheduleExceptions->unionAll($attendanceIncidents);

        $rows = $union->orderBy('date')->orderBy('employee_name')->get();

        return $rows->map(fn (array $row): AbsenteeismRowDTO => new AbsenteeismRowDTO(
            employeeId: (int) $row['employee_id'],
            employeeName: $row['employee_name'],
            employeeNumber: $row['employee_number'],
            teamName: $row['team_name'],
            date: $row['date'],
            originType: $row['origin_type'],
            causeName: $row['cause_name'],
            isJustified: (bool) $row['is_justified'],
            isFullDay: (bool) $row['is_full_day'],
            startAt: $row['start_at'] ?? $row['start_time'],
            endAt: $row['end_at'] ?? $row['end_time'],
            minutesAbsent: $row['minutes_absent'] !== null ? (int) round((float) $row['minutes_absent']) : null,
            remarks: $row['remarks'],
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

        return $query->get()->map(fn (array $row): ExceptionSummaryRowDTO => new ExceptionSummaryRowDTO(
            causeName: $row['name'],
            shortCode: $row['short_code'],
            isExcused: (bool) $row['is_excused'],
            totalOccurrences: (int) $row['total_occurrences'],
            totalMinutesLost: (int) round((float) $row['total_minutes_lost']),
            employeesAffected: (int) $row['employees_affected'],
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

        return $query->get()->map(fn (array $row): AhtRowDTO => new AhtRowDTO(
            agentName: $row['agent_name'],
            queueName: $row['queue_name'],
            date: $row['date'],
            callsHandled: (int) $row['calls_handled'],
            avgTalkTime: (float) ($row['avg_talk_time'] ?? 0),
            avgWorkTime: (float) ($row['avg_work_time'] ?? 0),
            avgHoldTime: (float) ($row['avg_hold_time'] ?? 0),
            aht: (float) ($row['aht'] ?? 0),
            ahtGoal: $row['aht_goal'] !== null ? (int) $row['aht_goal'] : null,
            deviation: $row['aht'] !== null && $row['aht_goal'] !== null ? (float) ($row['aht'] - $row['aht_goal']) : null,
            minAht: $row['min_aht'] !== null ? (float) $row['min_aht'] : null,
            maxAht: $row['max_aht'] !== null ? (float) $row['max_aht'] : null,
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

        return $query->get()->map(fn (array $row): AhtRowDTO => new AhtRowDTO(
            agentName: '—',
            queueName: $row['queue_name'],
            date: $row['date'],
            callsHandled: (int) $row['calls_handled'],
            avgTalkTime: (float) ($row['avg_talk_time'] ?? 0),
            avgWorkTime: (float) ($row['avg_work_time'] ?? 0),
            avgHoldTime: (float) ($row['avg_hold_time'] ?? 0),
            aht: (float) ($row['aht'] ?? 0),
            ahtGoal: $row['aht_goal'] !== null ? (int) $row['aht_goal'] : null,
            deviation: $row['aht'] !== null && $row['aht_goal'] !== null ? (float) ($row['aht'] - $row['aht_goal']) : null,
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
            ])
            ->join('call_queues', 'call_queues.id', '=', 'call_records.queue_id')
            ->whereDate('call_records.ivr_started_at', '>=', $filters->dateFrom)
            ->whereDate('call_records.ivr_started_at', '<=', $filters->dateTo)
            ->groupBy(
                'call_queues.name',
                DB::raw('call_records.ivr_started_at::date'),
            )
            ->orderBy('date')
            ->orderBy('queue_name');

        if ($filters->queueId !== null) {
            $query->where('call_records.queue_id', $filters->queueId);
        }

        return $query->get()->map(fn (array $row): VolumeRowDTO => new VolumeRowDTO(
            queueName: $row['queue_name'],
            date: $row['date'],
            received: (int) $row['received'],
            handled: (int) $row['handled'],
            abandoned: (int) $row['abandoned'],
            abandonmentRate: $row['received'] > 0 ? round(((int) $row['abandoned'] / (int) $row['received']) * 100, 2) : 0,
            aht: $row['aht'] !== null ? (float) $row['aht'] : null,
            asa: $row['asa'] !== null ? (float) $row['asa'] : null,
            maxWaitTime: $row['max_wait_time'] !== null ? (int) $row['max_wait_time'] : null,
            minWaitTime: $row['min_wait_time'] !== null ? (int) $row['min_wait_time'] : null,
            avgAbandonTime: $row['avg_abandon_time'] !== null ? (float) $row['avg_abandon_time'] : null,
            slaPercentage: $this->calculateSlaPercentage($row, self::SLA_THRESHOLD_SECONDS),
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

        return $query->get()->map(fn (array $row): VolumeRowDTO => new VolumeRowDTO(
            queueName: $row['queue_name'],
            date: $filters->interval,
            received: (int) $row['received'],
            handled: (int) $row['handled'],
            abandoned: (int) $row['abandoned'],
            abandonmentRate: $row['received'] > 0 ? round(((int) $row['abandoned'] / (int) $row['received']) * 100, 2) : 0,
            aht: $row['aht'] !== null ? (float) $row['aht'] : null,
            asa: $row['asa'] !== null ? (float) $row['asa'] : null,
        ));
    }

    private function applyAbsenteeismFilters(Builder $query, ReportFilterDTO $filters): Builder
    {
        if ($filters->teamId !== null) {
            $query->where('employees.team_id', $filters->teamId);
        }

        if ($filters->employeeId !== null) {
            $query->where('employees.id', $filters->employeeId);
        }

        if ($filters->justified !== null) {
            if ($query->getModel() instanceof AttendanceIncident) {
                if ($filters->justified) {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        return $query;
    }

    private function calculateSlaPercentage(array $row, int $thresholdSeconds): ?float
    {
        if ((int) $row['received'] === 0) {
            return null;
        }

        $query = CallRecord::query()
            ->whereDate('ivr_started_at', $row['date'])
            ->where('queue_id', function ($q) use ($row) {
                $q->select('id')->from('call_queues')->where('name', $row['queue_name']);
            });

        $withinSla = (clone $query)->where('queue_time', '<=', $thresholdSeconds)->count();
        $total = $query->count();

        return $total > 0 ? round(($withinSla / $total) * 100, 2) : null;
    }
}
