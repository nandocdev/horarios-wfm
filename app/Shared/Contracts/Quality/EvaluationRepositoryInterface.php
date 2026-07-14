<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Quality;

use App\Modules\QualityModule\Models\Evaluation;
use Illuminate\Pagination\LengthAwarePaginator;

interface EvaluationRepositoryInterface
{
    /**
     * @param  array{fecha_desde?: string|null, fecha_hasta?: string|null, queue_id?: string|null, evaluator_id?: int|null, employee_id?: int|null, status?: string|null}  $filters
     */
    public function paginated(array $filters, string $sortField = 'dteval', string $sortDirection = 'desc'): LengthAwarePaginator;

    public function find(string $id): ?Evaluation;

    public function avgScoreByQueue(string $queueId): float;

    public function countByStatus(string $status): int;
}
