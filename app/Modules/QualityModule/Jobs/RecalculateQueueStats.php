<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Jobs;

use App\Modules\QualityModule\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateQueueStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ReportService $reportService): void
    {
        $reportService->recalculateQueueAverages();
    }
}
