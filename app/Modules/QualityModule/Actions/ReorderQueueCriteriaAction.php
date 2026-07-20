<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Models\QueueCriteria;

class ReorderQueueCriteriaAction
{
    public function moveUp(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $prev = QueueCriteria::where('queue_id', $qc->queue_id)
            ->where('orden', '<', $qc->orden)
            ->orderByDesc('orden')
            ->first();

        if ($prev) {
            $temp = $qc->orden;
            $qc->update(['orden' => $prev->orden]);
            $prev->update(['orden' => $temp]);
        }
    }

    public function moveDown(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $next = QueueCriteria::where('queue_id', $qc->queue_id)
            ->where('orden', '>', $qc->orden)
            ->orderBy('orden')
            ->first();

        if ($next) {
            $temp = $qc->orden;
            $qc->update(['orden' => $next->orden]);
            $next->update(['orden' => $temp]);
        }
    }
}
