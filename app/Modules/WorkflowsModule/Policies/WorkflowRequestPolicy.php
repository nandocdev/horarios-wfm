<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;

class WorkflowRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('workflows.viewAny');
    }

    public function view(User $user, WorkflowRequest $workflow): bool
    {
        return $user->hasPermissionTo('workflows.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('workflows.create');
    }

    public function approve(User $user, WorkflowRequest $workflow): bool
    {
        return $user->hasPermissionTo('workflows.approve');
    }

    public function reject(User $user, WorkflowRequest $workflow): bool
    {
        return $user->hasPermissionTo('workflows.approve');
    }
}
