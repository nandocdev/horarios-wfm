<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Policies;

use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Controla la autorización administrativa de usuarios basada en RBAC y jerarquía.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede listar el staff del sistema.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('users.view');
    }

    public function view(User $authUser, User $target): bool
    {
        return $authUser->hasPermissionTo('users.view') || $authUser->id === $target->id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('users.create');
    }

    public function update(User $authUser, User $target): bool
    {
        if (! $authUser->hasPermissionTo('users.edit')) {
            return false;
        }

        // [UC-INT-04] Bloquear acciones fuera de jerarquía organizacional
        // Un usuario no puede editar a alguien con mayor jerarquía (nivel menor)
        return $this->checkHierarchy($authUser, $target);
    }

    public function delete(User $authUser, User $target): bool
    {
        // En este sistema no se borra físicamente, pero el permiso controla SoftDelete
        return $authUser->hasPermissionTo('users.delete') && $this->checkHierarchy($authUser, $target);
    }

    /**
     * Valida la jerarquía de roles entre el autorizador y el objetivo.
     */
    protected function checkHierarchy(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return true; // Puede editarse a sí mismo
        }

        $authMaxHierarchy = $authUser->roles()->min('hierarchy_level') ?? 0;
        $targetMaxHierarchy = $target->roles()->min('hierarchy_level') ?? 0;

        // Regla: Solo puedes administrar a usuarios con nivel de jerarquía menor o igual al tuyo
        return $authMaxHierarchy >= $targetMaxHierarchy;
    }
}
