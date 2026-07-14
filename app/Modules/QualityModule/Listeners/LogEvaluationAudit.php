<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\QualityModule\Events\EvaluationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogEvaluationAudit implements ShouldQueue
{
    public function handle(EvaluationCreated $event): void
    {
        AuditLog::log($event->evaluation, 'created');
    }
}
