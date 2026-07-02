<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Observers;

use App\Src\Identity\Infrastructure\Persistence\EloquentRole;
use Spatie\Permission\PermissionRegistrar;

class RoleObserver
{
    public function saved(EloquentRole $role): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function deleted(EloquentRole $role): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
