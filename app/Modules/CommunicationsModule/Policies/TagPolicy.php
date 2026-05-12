<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Tag;
use App\Modules\CoreModule\Models\User;

/**
 * Define los permisos de acceso al recurso Tag.
 */
class TagPolicy
{
    /**
     * Determina si el usuario puede ver cualquier tag.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede ver un tag específico.
     */
    public function view(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede crear tags.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede actualizar un tag.
     */
    public function update(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede eliminar un tag.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede restaurar un tag.
     */
    public function restore(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede eliminar permanentemente un tag.
     */
    public function forceDelete(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }
}
