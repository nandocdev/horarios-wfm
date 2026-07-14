<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Models\Evaluation;

final class DeleteEvaluationAction
{
    /**
     * RN-03: No se puede eliminar si tiene feedback o calibracion.
     */
    public function execute(string $evaluationId): bool
    {
        $evaluation = Evaluation::findOrFail($evaluationId);

        if ($evaluation->feedbacks()->exists()) {
            throw new \RuntimeException(
                'No se puede eliminar una evaluacion que tiene feedback asociado.'
            );
        }

        if ($evaluation->calibrations()->exists()) {
            throw new \RuntimeException(
                'No se puede eliminar una evaluacion que tiene calibracion asociada.'
            );
        }

        return (bool) $evaluation->delete();
    }
}
