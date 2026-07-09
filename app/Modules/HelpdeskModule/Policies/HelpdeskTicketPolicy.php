<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;

class HelpdeskTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.view');
    }

    public function view(User $user, HelpdeskTicket $ticket): bool
    {
        if ($user->hasPermissionTo('helpdesk.manage')) {
            return true;
        }

        if ($user->hasPermissionTo('helpdesk.view')) {
            return true;
        }

        $employee = $user->employee;

        return $employee && $ticket->creator_id === $employee->id;
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.manage');
    }

    public function update(User $user, HelpdeskTicket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.manage');
    }
}
