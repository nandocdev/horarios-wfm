<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CoreModule\Models\User;

final class AuditLogPolicy {
    public function viewAny(User $user): bool {
        return $user->can('audit.view') || $user->hasRole('admin');
    }

    public function view(User $user): bool {
        return $user->can('audit.view') || $user->hasRole('admin');
    }

    public function export(User $user): bool {
        return $user->can('audit.export') || $user->hasRole('admin');
    }
}
