<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\QualityModule\Events\CriteriaVersionCreated;

class LogCriteriaVersionCreated
{
    public function handle(CriteriaVersionCreated $event): void
    {
        AuditLog::log($event->criteriaVersion, 'created');
    }
}
