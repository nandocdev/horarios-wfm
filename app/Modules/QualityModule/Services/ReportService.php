<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Services;

use App\Modules\QualityModule\Enums\EvaluationStatus;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Queue;
use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    public function __construct(
        private readonly CachePolicyService $cachePolicy,
    ) {}

    public function getQueueAverages(): array
    {
        return $this->cachePolicy->remember('quality', 'quality', 'dashboard:averages', function () {
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
        $this->cachePolicy->flushByPattern('quality', 'quality');

        $queues = Queue::where('is_active', true)->get();
        foreach ($queues as $queue) {
            Cache::forget("quality:queue_avg:{$queue->id}");
        }

        // Trigger cache population
        $this->getQueueAverages();
    }
}
