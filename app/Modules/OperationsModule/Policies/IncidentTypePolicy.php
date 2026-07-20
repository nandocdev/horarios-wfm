<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Models\IncidentType;

class IncidentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('incident_types.viewAny');
    }

    public function view(User $user, IncidentType $incidentType): bool
    {
        return $user->hasPermissionTo('incident_types.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('incident_types.create');
    }

    public function update(User $user, IncidentType $incidentType): bool
    {
        return $user->hasPermissionTo('incident_types.update');
    }

    public function delete(User $user, IncidentType $incidentType): bool
    {
        return $user->hasPermissionTo('incident_types.delete');
    }
}
