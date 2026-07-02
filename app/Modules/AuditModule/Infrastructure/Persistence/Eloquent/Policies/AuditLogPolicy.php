<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Policies;

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\AuditLogModel;
use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('audit.view') || $user->hasRole('admin');
    }

    public function view(User $user, AuditLogModel $auditLog): bool
    {
        return $user->can('audit.view') || $user->hasRole('admin');
    }

    public function export(User $user): bool
    {
        return $user->can('audit.export') || $user->hasRole('admin');
    }
}
