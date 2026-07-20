<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Models\QueueCriteria;

class RemoveCriteriaFromQueueAction
{
    public function execute(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $qc->delete();
    }
}
