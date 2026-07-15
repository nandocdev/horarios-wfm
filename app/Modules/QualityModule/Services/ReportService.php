<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Services;

use App\Modules\QualityModule\Enums\EvaluationStatus;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Queue;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    public function getQueueAverages(): array
    {
        return Cache::remember('quality:dashboard:averages', 86400, function () {
            $queues = Queue::where('is_active', true)->get();
            $averages = [];

            foreach ($queues as $queue) {
                $avg = Evaluation::where('queue_id', $queue->id)
                    ->where('status', EvaluationStatus::Activa)
                    ->avg('score');

                $averages[$queue->id] = [
                    'name' => $queue->name,
                    'code' => $queue->code,
                    'average' => round((float) $avg, 2),
                ];
            }

            return $averages;
        });
    }

    public function recalculateQueueAverages(): void
    {
        Cache::forget('quality:dashboard:averages');

        $queues = Queue::where('is_active', true)->get();
        foreach ($queues as $queue) {
            Cache::forget("quality:queue_avg:{$queue->id}");
        }

        // Trigger cache population
        $this->getQueueAverages();
    }
}
