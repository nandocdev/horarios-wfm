<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Observers;

use App\Modules\QualityModule\Models\Evaluation;

class EvaluationObserver
{
    public function creating(Evaluation $evaluation): void
    {
        $evaluation->dteval ??= now()->toDateString();
        $evaluation->tmeval ??= now()->toTimeString();
    }

    public function deleting(Evaluation $evaluation): void
    {
        if ($evaluation->isForceDeleting()) {
            return;
        }

        $evaluation->status = 'eliminada';
        $evaluation->saveQuietly();
    }
}
