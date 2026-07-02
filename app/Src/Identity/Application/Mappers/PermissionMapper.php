<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Mappers;

use App\Src\Identity\Domain\Entities\Permission;

final class PermissionMapper
{
    public static function toDomain(\App\Src\Identity\Infrastructure\Persistence\EloquentPermission $eloquent): Permission
    {
        return Permission::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            guardName: $eloquent->guard_name ?? 'web',
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                ? $eloquent->created_at
                : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
        );
    }

    public static function toEloquent(Permission $permission): array
    {
        return [
            'name' => $permission->name(),
            'guard_name' => $permission->guardName(),
        ];
    }

    public static function toArray(Permission $permission): array
    {
        return [
            'id' => $permission->id(),
            'name' => $permission->name(),
            'guard_name' => $permission->guardName(),
            'created_at' => $permission->createdAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
