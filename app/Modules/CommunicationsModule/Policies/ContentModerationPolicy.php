<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Define permisos de moderación para contenido del módulo Communications.
 */
class ContentModerationPolicy
{
    /**
     * Verifica si el usuario puede moderar contenido.
     */
    public function moderate(User $user): bool
    {
        return $user->hasPermissionTo('communications.moderate');
    }

    /**
     * Verifica si el usuario puede aprobar contenido.
     */
    public function approve(User $user): bool
    {
        return $user->hasPermissionTo('communications.approve');
    }

    /**
     * Verifica si el usuario puede rechazar contenido.
     */
    public function reject(User $user): bool
    {
        return $user->hasPermissionTo('communications.reject');
    }

    /**
     * Verifica si el usuario puede archivar contenido.
     */
    public function archive(User $user): bool
    {
        return $user->hasPermissionTo('communications.archive');
    }

    /**
     * Verifica si el usuario puede ver contenido pendiente de revisión.
     */
    public function viewPending(User $user): bool
    {
        return $user->hasPermissionTo('communications.view_pending');
    }

    /**
     * Verifica si el usuario puede moderar contenido específico.
     * Por ahora, moderadores pueden moderar todo el contenido.
     * TODO: Implementar control por organización cuando esté disponible.
     */
    public function moderateContent(User $user, Model $content): bool
    {
        return $user->hasRole(['admin', 'moderator', 'owner']);
    }
}
