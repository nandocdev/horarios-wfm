<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Observers;

use App\Modules\CoreModule\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Observador para invalidar caché de permisos cuando roles cambian.
 */
class RoleObserver
{
    public function saved(Role $role): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function deleted(Role $role): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
