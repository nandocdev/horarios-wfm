<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use App\Src\Identity\Domain\Entities\Permission;
use App\Src\Identity\Domain\Repositories\PermissionRepositoryInterface;
use Spatie\Permission\PermissionRegistrar;

final class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission
    {
        $eloquent = EloquentPermission::find($id);

        if ($eloquent === null) {
            return null;
        }

        return Permission::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            guardName: $eloquent->guard_name ?? 'web',
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                ? $eloquent->created_at
                : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
        );
    }

    public function findByName(string $name): ?Permission
    {
        $eloquent = EloquentPermission::where('name', $name)->first();

        if ($eloquent === null) {
            return null;
        }

        return Permission::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            guardName: $eloquent->guard_name ?? 'web',
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                ? $eloquent->created_at
                : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
        );
    }

    public function all(): array
    {
        return EloquentPermission::orderBy('name')
            ->get()
            ->map(fn ($eloquent) => Permission::fromDatabase(
                id: $eloquent->id,
                name: $eloquent->name,
                guardName: $eloquent->guard_name ?? 'web',
                createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                    ? $eloquent->created_at
                    : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
            ))
            ->toArray();
    }

    public function create(string $name, string $guardName = 'web'): Permission
    {
        $eloquent = EloquentPermission::create([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return Permission::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            guardName: $eloquent->guard_name ?? 'web',
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                ? $eloquent->created_at
                : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
        );
    }

    public function syncToRole(int $roleId, array $permissionIds): void
    {
        $role = EloquentRole::find($roleId);

        if ($role !== null) {
            $role->syncPermissions($permissionIds);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
