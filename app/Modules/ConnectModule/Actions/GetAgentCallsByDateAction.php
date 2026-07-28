<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\AgentCallPerformance;

class GetAgentCallsByDateAction
{
    public function execute(string $agentLoginId, string $date): array
    {
        return AgentCallPerformance::with([])
            ->where('agent_login_id', $agentLoginId)
            ->whereDate('start_time', $date)
            ->orderByDesc('start_time')
            ->get()
            ->toArray();
    }
}
