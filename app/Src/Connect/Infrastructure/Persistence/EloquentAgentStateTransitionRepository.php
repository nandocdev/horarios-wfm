<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\AgentStateTransition;
use App\Src\Connect\Domain\Repositories\AgentStateTransitionRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentAgentStateTransitionRepository implements AgentStateTransitionRepositoryInterface
{
    public function save(AgentStateTransition $transition): AgentStateTransition
    {
        $data = ConnectMapper::agentStateTransitionToEloquent($transition);

        if ($transition->id() !== null) {
            $eloquent = EloquentAgentStateTransition::findOrFail($transition->id());
            $eloquent->update($data);
        } else {
            $eloquent = EloquentAgentStateTransition::create($data);
        }

        return ConnectMapper::agentStateTransitionToDomain($eloquent->fresh());
    }

    public function bulkInsert(array $transitions): void
    {
        if (empty($transitions)) {
            return;
        }

        $records = [];
        foreach ($transitions as $transition) {
            $records[] = ConnectMapper::agentStateTransitionToEloquent($transition);
        }

        DB::table('agent_state_transitions')->insert($records);
    }

    public function findByEmployee(int $employeeId, string $dateFrom, string $dateTo): array
    {
        return EloquentAgentStateTransition::where('employee_id', $employeeId)
            ->whereBetween('transition_time', [$dateFrom, $dateTo])
            ->orderBy('transition_time')
            ->get()
            ->map(fn (EloquentAgentStateTransition $e) => ConnectMapper::agentStateTransitionToDomain($e))
            ->toArray();
    }

    public function findLatestByEmployee(int $employeeId): ?AgentStateTransition
    {
        $eloquent = EloquentAgentStateTransition::where('employee_id', $employeeId)
            ->orderByDesc('transition_time')
            ->first();

        return $eloquent ? ConnectMapper::agentStateTransitionToDomain($eloquent) : null;
    }
}
