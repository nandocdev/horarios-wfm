<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Policies;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermissionTo('audit.view');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('audit.export');
    }
}
