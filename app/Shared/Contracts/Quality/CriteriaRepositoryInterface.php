<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Quality;

use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use Illuminate\Support\Collection;

interface CriteriaRepositoryInterface
{
    /**
     * @return Collection<int, CriteriaVersion>
     */
    public function getActiveByQueue(string $queueId): Collection;

    /**
     * @return Collection<int, Criteria>
     */
    public function all(): Collection;
}
