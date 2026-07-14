<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Repositories;

use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use App\Shared\Contracts\Quality\CriteriaRepositoryInterface;
use Illuminate\Support\Collection;

final class EloquentCriteriaRepository implements CriteriaRepositoryInterface
{
    public function getActiveByQueue(string $queueId): Collection
    {
        return CriteriaVersion::whereHas('queueCriteria', fn ($q) => $q
            ->where('queue_id', $queueId)
            ->where('is_active', true)
        )
            ->with(['criteria', 'queueCriteria' => fn ($q) => $q->where('queue_id', $queueId)])
            ->whereNull('valid_to')
            ->get()
            ->sortBy(fn ($v) => $v->queueCriteria->first()?->orden ?? 0)
            ->values();
    }

    public function all(): Collection
    {
        return Criteria::with(['currentVersion'])->get();
    }
}
