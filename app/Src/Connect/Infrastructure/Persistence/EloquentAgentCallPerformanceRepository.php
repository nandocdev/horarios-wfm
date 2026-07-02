<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\AgentCallPerformance;
use App\Src\Connect\Domain\Repositories\AgentCallPerformanceRepositoryInterface;

final class EloquentAgentCallPerformanceRepository implements AgentCallPerformanceRepositoryInterface
{
    public function save(AgentCallPerformance $performance): AgentCallPerformance
    {
        $data = ConnectMapper::agentCallPerformanceToEloquent($performance);

        if ($performance->id() !== null) {
            $eloquent = EloquentAgentCallPerformance::findOrFail($performance->id());
            $eloquent->update($data);
        } else {
            $eloquent = EloquentAgentCallPerformance::create($data);
        }

        return ConnectMapper::agentCallPerformanceToDomain($eloquent->fresh());
    }

    public function upsert(AgentCallPerformance $performance): AgentCallPerformance
    {
        $data = ConnectMapper::agentCallPerformanceToEloquent($performance);

        $eloquent = EloquentAgentCallPerformance::updateOrCreate(
            [
                'employee_id' => $performance->employeeId(),
                'csq_name' => $performance->csqName(),
                'start_time' => $performance->startTime()?->format('Y-m-d H:i:s'),
            ],
            $data,
        );

        return ConnectMapper::agentCallPerformanceToDomain($eloquent->fresh());
    }

    public function findById(int $id): ?AgentCallPerformance
    {
        $eloquent = EloquentAgentCallPerformance::find($id);
        return $eloquent ? ConnectMapper::agentCallPerformanceToDomain($eloquent) : null;
    }

    public function findByEmployee(int $employeeId, string $date): array
    {
        return EloquentAgentCallPerformance::where('employee_id', $employeeId)
            ->whereDate('start_time', $date)
            ->get()
            ->map(fn (EloquentAgentCallPerformance $e) => ConnectMapper::agentCallPerformanceToDomain($e))
            ->toArray();
    }

    public function findByDateRange(string $dateFrom, string $dateTo): array
    {
        return EloquentAgentCallPerformance::whereBetween('start_time', [$dateFrom, $dateTo])
            ->orderBy('start_time')
            ->get()
            ->map(fn (EloquentAgentCallPerformance $e) => ConnectMapper::agentCallPerformanceToDomain($e))
            ->toArray();
    }
}
