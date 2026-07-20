<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\QueueCriteria;

class AssignCriteriaToQueueAction
{
    public function execute(string $queueId, string $criteriaId): void
    {
        $criteria = Criteria::findOrFail($criteriaId);
        $currentVersion = $criteria->currentVersion;

        if (! $currentVersion) {
            throw new \RuntimeException('El criterio no tiene una versión activa.');
        }

        $exists = QueueCriteria::where('queue_id', $queueId)
            ->where('criteria_version_id', $currentVersion->id)
            ->exists();

        if ($exists) {
            throw new \RuntimeException('Este criterio ya está asignado a la cola.');
        }

        $maxOrden = QueueCriteria::where('queue_id', $queueId)->max('orden') ?? 0;

        QueueCriteria::create([
            'queue_id' => $queueId,
            'criteria_version_id' => $currentVersion->id,
            'orden' => $maxOrden + 1,
            'is_active' => true,
        ]);
    }
}
