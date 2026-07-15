<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Repositories;

use App\Modules\QualityModule\Models\Evaluation;
use App\Shared\Contracts\Quality\EvaluationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentEvaluationRepository implements EvaluationRepositoryInterface
{
    public function paginated(array $filters, string $sortField = 'dteval', string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = Evaluation::with(['queue', 'employee', 'evaluator', 'scores', 'feedbacks', 'calibrations']);

        if (! empty($filters['fecha_desde'])) {
            $query->whereDate('dteval', '>=', $filters['fecha_desde']);
        }

        if (! empty($filters['fecha_hasta'])) {
            $query->whereDate('dteval', '<=', $filters['fecha_hasta']);
        }

        if (! empty($filters['queue_id'])) {
            $query->where('queue_id', $filters['queue_id']);
        }

        if (! empty($filters['team_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('team_id', $filters['team_id']);
            });
        }

        if (! empty($filters['evaluator_id'])) {
            $query->where('evaluator_id', $filters['evaluator_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy($sortField, $sortDirection)
            ->paginate(25);
    }

    public function find(string $id): ?Evaluation
    {
        return Evaluation::with(['queue', 'scores.criteriaVersion', 'feedbacks', 'calibrations'])
            ->find($id);
    }

    public function avgScoreByQueue(string $queueId): float
    {
        return (float) Evaluation::where('queue_id', $queueId)
            ->where('status', 'activa')
            ->avg('score');
    }

    public function countByStatus(string $status): int
    {
        return Evaluation::where('status', $status)->count();
    }
}
