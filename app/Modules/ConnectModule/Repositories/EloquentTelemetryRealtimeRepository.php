<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Repositories;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\CsqRealtimeStat;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentTelemetryRealtimeRepository implements TelemetryRealtimeRepositoryInterface
{
    public function getRealtimeStates(?array $employeeIds = null): Collection
    {
        $query = AgentRealtimeState::query();

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->get();
    }

    public function getLatestUpdate(): ?string
    {
        $max = AgentRealtimeState::max('updated_at');

        return $max ? Carbon::parse($max)->toDateTimeString() : null;
    }

    public function getDistinctReasonCodes(): Collection
    {
        return AgentRealtimeState::whereNotNull('reason_code')
            ->distinct()
            ->pluck('reason_code')
            ->sort()
            ->values();
    }

    public function getAgentHistory(int $employeeId, string $date, int $limit = 10): Collection
    {
        return AgentStateTransition::where('employee_id', $employeeId)
            ->whereDate('transition_time', $date)
            ->orderBy('transition_time', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getQueueAhtGoals(): Collection
    {
        return CallQueue::pluck('aht_goal', 'name');
    }

    public function getAllQueues(): Collection
    {
        return CallQueue::all();
    }

    public function getQueueStats(int $limit = 6): Collection
    {
        return CsqRealtimeStat::orderByDesc('calls_waiting')
            ->take($limit)
            ->get()
            ->map(fn ($q): array => [
                'name' => $q->csq_name,
                'waiting' => $q->calls_waiting,
                'handled' => $q->calls_handled_since_midnight ?? 0,
                'abandoned' => $q->calls_abandoned_since_midnight ?? 0,
                'abandon_rate' => ($q->total_calls_since_midnight ?? 0) > 0
                    ? round(($q->calls_abandoned_since_midnight / $q->total_calls_since_midnight) * 100, 1)
                    : 0.0,
                'aht' => '—',
                'sla' => $q->service_level_long_term ? round($q->service_level_long_term, 1).'%' : '—',
                'state' => ($q->service_level_long_term ?? 100) < 80 ? 'critical' : (($q->service_level_long_term ?? 100) < 90 ? 'attention' : 'normal'),
            ]);
    }

    public function getCsqRealtimeStats(): Collection
    {
        return CsqRealtimeStat::orderByDesc('calls_waiting')
            ->get();
    }

    public function getCallTrends(string $from, string $to): Collection
    {
        return DB::table('agent_call_performance')
            ->whereDate('start_time', '>=', $from)
            ->whereDate('start_time', '<=', $to)
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('COUNT(*) as calls'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('calls');
    }

    public function getStateDistribution(?array $employeeIds = null): array
    {
        $query = AgentRealtimeState::query();

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        $groups = $query
            ->selectRaw('current_state, COUNT(*) as cnt')
            ->groupBy('current_state')
            ->pluck('cnt', 'current_state');

        return [
            'operating' => (int) ($groups->get('TALKING', 0) + $groups->get('WORK', 0) + $groups->get('RESERVED', 0)),
            'ready' => (int) $groups->get('READY', 0),
            'auxiliar' => (int) $groups->get('NOT_READY', 0),
            'offline' => (int) ($groups->get('LOGOUT', 0) + $groups->get('OFFLINE', 0) + $groups->get('UNKNOWN', 0)),
        ];
    }

    public function getBatchStateTransitions(array $employeeIds, string $date): Collection
    {
        return AgentStateTransition::whereIn('employee_id', $employeeIds)
            ->whereDate('transition_time', $date)
            ->get();
    }

    public function getCallStatsForDate(string $date, ?array $employeeIds = null): object
    {
        $query = CallRecord::whereNotNull('queue_id')
            ->whereDate('ivr_started_at', $date);

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled')
        )
            ->first() ?? (object) ['total' => 0, 'handled' => 0];
    }

    public function getAverageServiceLevel(): float
    {
        return (float) (CsqRealtimeStat::avg('service_level_long_term') ?? 0);
    }

    public function getTalkingAgentsByQueue(array $employeeIds): Collection
    {
        return AgentRealtimeState::whereIn('employee_id', $employeeIds)
            ->where('current_state', 'TALKING')
            ->get()
            ->groupBy(fn ($agent) => $agent->metadata['call_info']['queue_name'] ?? 'OTROS')
            ->map->count();
    }

    public function getHistoricalStateDistribution(array $employeeIds, string $date): array
    {
        $states = AgentStateTransition::whereIn('employee_id', $employeeIds)
            ->whereDate('transition_time', $date)
            ->select('agent_state', DB::raw('count(distinct employee_id) as count'))
            ->groupBy('agent_state')
            ->get()
            ->pluck('count', 'agent_state')
            ->toArray();

        return [
            'Ready' => (int) ($states['READY'] ?? 0),
            'Talking' => (int) ($states['TALKING'] ?? 0),
            'AUX' => (int) ($states['NOT_READY'] ?? 0) + (int) ($states['WORK'] ?? 0),
            'Offline' => (int) ($states['LOGOUT'] ?? 0) + (int) ($states['OFFLINE'] ?? 0),
        ];
    }

    public function getCurrentStateDistribution(array $employeeIds): array
    {
        $states = AgentRealtimeState::whereIn('employee_id', $employeeIds)
            ->select('current_state', DB::raw('count(*) as count'))
            ->groupBy('current_state')
            ->get()
            ->pluck('count', 'current_state')
            ->toArray();

        return [
            'Ready' => (int) ($states['READY'] ?? 0),
            'Talking' => (int) ($states['TALKING'] ?? 0),
            'AUX' => (int) ($states['NOT_READY'] ?? 0) + (int) ($states['WORK'] ?? 0),
            'Offline' => (int) ($states['LOGOUT'] ?? 0) + (int) ($states['OFFLINE'] ?? 0),
        ];
    }

    public function getQueuePerformanceReport(string $date, ?array $employeeIds = null): Collection
    {
        $query = CallRecord::join('call_queues', 'call_records.queue_id', '=', 'call_queues.id')
            ->whereDate('ivr_started_at', $date);

        if ($employeeIds !== null) {
            $query->whereIn('call_records.employee_id', $employeeIds);
        }

        return $query->select(
            'call_queues.name as queue_name',
            'call_queues.aht_goal',
            DB::raw('COUNT(*) as total_offered'),
            DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
            DB::raw('SUM(CASE WHEN contact_disposition = 1 THEN 1 ELSE 0 END) as abandoned'),
            DB::raw('AVG(talk_time + work_time) as avg_aht'),
            DB::raw('AVG(talk_time) as avg_talk'),
            DB::raw('AVG(work_time) as avg_work'),
            DB::raw('SUM(talk_time) as total_talk'),
            DB::raw('SUM(work_time) as total_work'),
            DB::raw('AVG(queue_time) as avg_asa'),
            DB::raw('MAX(queue_time) as max_wait'),
            DB::raw('SUM(CASE WHEN contact_disposition = 2 AND queue_time <= 20 THEN 1 ELSE 0 END) as sl_count'),
        )
            ->groupBy('call_queues.id', 'call_queues.name', 'call_queues.aht_goal')
            ->orderBy('total_offered', 'desc')
            ->get();
    }

    public function getCallVolumeByDateRange(string $start, string $end): Collection
    {
        return CallRecord::whereNotNull('queue_id')
            ->whereBetween('ivr_started_at', [$start, $end])
            ->select(
                DB::raw('DATE(ivr_started_at) as date'),
                DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
                DB::raw('SUM(CASE WHEN contact_disposition = 1 THEN 1 ELSE 0 END) as abandoned'),
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');
    }
}
