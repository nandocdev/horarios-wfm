<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Policies;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\CoreModule\Models\User;

class AgentRealtimeStatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('agent_states.viewAny');
    }

    public function view(User $user, AgentRealtimeState $agentRealtimeState): bool
    {
        return $user->hasPermissionTo('agent_states.viewAny');
    }
}
