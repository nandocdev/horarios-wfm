<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'wfm']);
    }

    public function view(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'wfm']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'wfm']);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'wfm']);
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'wfm']);
    }
}
