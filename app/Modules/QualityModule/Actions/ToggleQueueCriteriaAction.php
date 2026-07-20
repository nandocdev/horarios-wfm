<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Models\QueueCriteria;

class ToggleQueueCriteriaAction
{
    public function execute(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $qc->update(['is_active' => ! $qc->is_active]);
    }
}
