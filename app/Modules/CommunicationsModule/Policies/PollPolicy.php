<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CoreModule\Models\User;

/**
 * Policy para control de acceso a encuestas.
 */
class PollPolicy
{
    /**
     * Determina si el usuario puede ver cualquier encuesta.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('polls.manage');
    }

    /**
     * Determina si el usuario puede ver una encuesta específica.
     */
    public function view(User $user, Poll $poll): bool
    {
        return $user->hasPermissionTo('polls.manage');
    }

    /**
     * Determina si el usuario puede crear encuestas.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('polls.manage');
    }

    /**
     * Determina si el usuario puede actualizar una encuesta.
     */
    public function update(User $user, Poll $poll): bool
    {
        return $user->hasPermissionTo('polls.manage');
    }

    /**
     * Determina si el usuario puede eliminar una encuesta.
     */
    public function delete(User $user, Poll $poll): bool
    {
        return $user->hasPermissionTo('polls.manage');
    }

    /**
     * Determina si el usuario puede archivar (cerrar) una encuesta.
     */
    public function archive(User $user, Poll $poll): bool
    {
        return $user->hasPermissionTo('polls.manage') || $user->hasPermissionTo('communications.moderate');
    }

    /**
     * Determina si el usuario puede moderar este contenido.
     */
    public function moderateContent(User $authUser, Poll $poll): bool
    {
        return $authUser->hasPermissionTo('communications.moderate');
    }
}
