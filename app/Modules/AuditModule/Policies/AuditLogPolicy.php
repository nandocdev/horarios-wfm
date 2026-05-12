<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Policies;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('audit.view') || $user->hasRole('admin');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->can('audit.view') || $user->hasRole('admin');
    }

    public function export(User $user): bool
    {
        return $user->can('audit.export') || $user->hasRole('admin');
    }
}
