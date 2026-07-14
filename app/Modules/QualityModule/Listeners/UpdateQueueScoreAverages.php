<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Listeners;

use App\Modules\QualityModule\Events\CalibrationCreated;
use App\Modules\QualityModule\Events\EvaluationCreated;
use Illuminate\Support\Facades\Cache;

class UpdateQueueScoreAverages
{
    public function handle(EvaluationCreated|CalibrationCreated $event): void
    {
        $evaluation = match (true) {
            $event instanceof EvaluationCreated => $event->evaluation,
            $event instanceof CalibrationCreated => $event->calibration->evaluation,
        };

        if (! $evaluation?->queue_id) {
            return;
        }

        Cache::forget("quality:queue_avg:{$evaluation->queue_id}");
        Cache::forget('quality:dashboard:averages');
    }
}
