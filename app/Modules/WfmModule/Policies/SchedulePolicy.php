<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchedulePolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver cualquier horario.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('schedules.manage');
    }

    /**
     * Determina si el usuario puede ver un horario específico.
     */
    public function view(User $user): bool
    {
        return $user->can('schedules.view_own')
            || $user->can('schedules.view_team')
            || $user->can('schedules.view_all')
            || $user->can('schedules.manage');
    }

    /**
     * Determina si el usuario puede crear horarios.
     */
    public function create(User $user): bool
    {
        return $user->can('schedules.manage');
    }

    /**
     * Determina si el usuario puede actualizar horarios.
     */
    public function update(User $user): bool
    {
        return $user->can('schedules.manage');
    }

    /**
     * Determina si el usuario puede eliminar horarios.
     */
    public function delete(User $user): bool
    {
        return $user->can('schedules.manage');
    }
    /**
     * Determina si el usuario puede ver el monitoreo en tiempo real.
     */
    public function monitorRealtime(User $user): bool
    {
        return $user->can('realtime.view')
            || $user->can('schedules.manage')
            || ($user->employee?->hasCoordinatorRights() ?? false);
    }
}
