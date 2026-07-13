<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Repositories;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\CsqRealtimeStat;
use App\Shared\Contracts\Telemetry\AgentRealtimeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentAgentRealtimeRepository implements AgentRealtimeRepositoryInterface
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
                'handled' => $q->calls_handled ?? 0,
                'aht' => $q->avg_handle_time ? gmdate('i:s', (int) $q->avg_handle_time) : '0:00',
                'sla' => $q->service_level ? round($q->service_level, 1).'%' : '—',
                'state' => ($q->service_level ?? 100) < 80 ? 'critical' : (($q->service_level ?? 100) < 90 ? 'attention' : 'normal'),
            ]);
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

    public function getCallStatsForDate(string $date): object
    {
        return CallRecord::whereNotNull('queue_id')
            ->whereDate('ivr_started_at', $date)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled')
            )
            ->first() ?? (object) ['total' => 0, 'handled' => 0];
    }

    public function getAverageServiceLevel(): float
    {
        return (float) (CsqRealtimeStat::avg('service_level_long_term') ?? 0);
    }
}
