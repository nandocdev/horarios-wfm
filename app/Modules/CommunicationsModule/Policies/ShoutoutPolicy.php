<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\CoreModule\Models\User;

/**
 * Policy para control de acceso a shoutouts.
 */
class ShoutoutPolicy
{
    /**
     * Determina si el usuario puede ver cualquier shoutout.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('shoutouts.manage');
    }

    /**
     * Determina si el usuario puede ver un shoutout específico.
     */
    public function view(User $user, Shoutout $shoutout): bool
    {
        return $user->hasPermissionTo('shoutouts.manage');
    }

    /**
     * Determina si el usuario puede crear shoutouts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shoutouts.manage');
    }

    /**
     * Determina si el usuario puede actualizar un shoutout.
     */
    public function update(User $user, Shoutout $shoutout): bool
    {
        return $user->hasPermissionTo('shoutouts.manage');
    }

    /**
     * Determina si el usuario puede eliminar un shoutout.
     */
    public function delete(User $user, Shoutout $shoutout): bool
    {
        return $user->hasPermissionTo('shoutouts.manage');
    }

    /**
     * Determina si el usuario puede moderar este contenido.
     */
    public function moderateContent(User $authUser, Shoutout $shoutout): bool
    {
        return $authUser->hasPermissionTo('communications.moderate');
    }
}
