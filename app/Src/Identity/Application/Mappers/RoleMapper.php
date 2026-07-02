<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Mappers;

use App\Src\Identity\Domain\Entities\Permission;
use App\Src\Identity\Domain\Entities\Role;
use App\Src\Identity\Infrastructure\Persistence\EloquentRole;

final class RoleMapper
{
    public static function toDomain(EloquentRole $eloquent): Role
    {
        $permissions = $eloquent->relationLoaded('permissions')
            ? $eloquent->permissions->map(fn ($perm) => Permission::fromDatabase(
                id: $perm->id,
                name: $perm->name,
                guardName: $perm->guard_name ?? 'web',
                createdAt: $perm->created_at instanceof \DateTimeImmutable
                    ? $perm->created_at
                    : new \DateTimeImmutable($perm->created_at->format('Y-m-d H:i:s')),
            ))->toArray()
            : [];

        return Role::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            code: $eloquent->code ?? $eloquent->name,
            hierarchyLevel: (int) ($eloquent->hierarchy_level ?? 0),
            guardName: $eloquent->guard_name ?? 'web',
            permissions: $permissions,
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                ? $eloquent->created_at
                : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
            updatedAt: $eloquent->updated_at instanceof \DateTimeImmutable
                ? $eloquent->updated_at
                : new \DateTimeImmutable($eloquent->updated_at->format('Y-m-d H:i:s')),
        );
    }

    public static function toEloquent(Role $role, ?EloquentRole $eloquent = null): EloquentRole
    {
        $model = $eloquent ?? new EloquentRole();

        $model->name = $role->name();
        $model->code = $role->code();
        $model->hierarchy_level = $role->hierarchyLevel();
        $model->guard_name = $role->guardName();

        if ($role->id() !== null) {
            $model->id = $role->id();
        }

        return $model;
    }
}
