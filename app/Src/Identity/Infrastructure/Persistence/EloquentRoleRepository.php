<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use App\Src\Identity\Application\Mappers\RoleMapper;
use App\Src\Identity\Domain\Entities\Role;
use App\Src\Identity\Domain\Repositories\RoleRepositoryInterface;
use Spatie\Permission\PermissionRegistrar;

final class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function save(Role $role): Role
    {
        $existing = $role->id() !== null
            ? EloquentRole::find($role->id())
            : null;

        $eloquent = RoleMapper::toEloquent($role, $existing);
        $eloquent->save();

        if (! empty($role->permissions())) {
            $permissionNames = array_map(fn ($perm) => $perm->name(), $role->permissions());
            $spatiePermissions = EloquentPermission::whereIn('name', $permissionNames)->get();
            $eloquent->syncPermissions($spatiePermissions->pluck('name')->toArray());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return RoleMapper::toDomain($eloquent->fresh()->load('permissions'));
    }

    public function findById(int $id): ?Role
    {
        $eloquent = EloquentRole::with('permissions')->find($id);

        if ($eloquent === null) {
            return null;
        }

        return RoleMapper::toDomain($eloquent);
    }

    public function findByName(string $name): ?Role
    {
        $eloquent = EloquentRole::with('permissions')
            ->where('name', $name)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return RoleMapper::toDomain($eloquent);
    }

    public function findByCode(string $code): ?Role
    {
        $eloquent = EloquentRole::with('permissions')
            ->where('code', $code)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return RoleMapper::toDomain($eloquent);
    }

    public function search(array $filters = [], int $perPage = 25): array
    {
        $query = EloquentRole::with('permissions');

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['hierarchy_level'])) {
            $query->where('hierarchy_level', $filters['hierarchy_level']);
        }

        $paginator = $query->orderBy('hierarchy_level', 'asc')->paginate($perPage);

        $paginator->through(fn (EloquentRole $eloquent) => RoleMapper::toDomain($eloquent));

        return $paginator->items();
    }

    public function delete(Role $role): void
    {
        EloquentRole::where('id', $role->id())->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function all(): array
    {
        return EloquentRole::with('permissions')
            ->orderBy('hierarchy_level', 'asc')
            ->get()
            ->map(fn (EloquentRole $eloquent) => RoleMapper::toDomain($eloquent))
            ->toArray();
    }
}
