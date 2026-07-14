<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\QualityModule\Events\FeedbackAdded;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogFeedbackAudit implements ShouldQueue
{
    public function handle(FeedbackAdded $event): void
    {
        AuditLog::log($event->feedback, 'created');
    }
}
