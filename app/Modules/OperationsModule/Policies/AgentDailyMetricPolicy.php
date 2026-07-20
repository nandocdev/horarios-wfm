<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Models\AgentDailyMetric;

class AgentDailyMetricPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('agent_daily_metrics.viewAny');
    }

    public function view(User $user, AgentDailyMetric $agentDailyMetric): bool
    {
        return $user->hasPermissionTo('agent_daily_metrics.viewAny');
    }
}
