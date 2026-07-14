<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Listeners;

use App\Modules\QualityModule\Events\EvaluationCreated;
use Illuminate\Support\Facades\Log;

class SendEvaluationNotification
{
    public function handle(EvaluationCreated $event): void
    {
        $evaluation = $event->evaluation;

        Log::info('[Quality] Evaluacion creada para notificar', [
            'evaluation_id' => $evaluation->id,
            'employee_id' => $evaluation->employee_id,
            'score' => $evaluation->score,
        ]);
    }
}
