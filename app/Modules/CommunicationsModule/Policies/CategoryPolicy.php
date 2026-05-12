<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CoreModule\Models\User;

/**
 * Define los permisos de acceso al recurso Category.
 */
class CategoryPolicy
{
    /**
     * Determina si el usuario puede ver cualquier categoría.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede ver una categoría específica.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede crear categorías.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede actualizar una categoría.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede eliminar una categoría.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede restaurar una categoría.
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }

    /**
     * Determina si el usuario puede eliminar permanentemente una categoría.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('communications.manage');
    }
}
