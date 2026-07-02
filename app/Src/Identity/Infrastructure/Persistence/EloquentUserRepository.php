<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use App\Src\Identity\Application\Mappers\UserMapper;
use App\Src\Identity\Domain\Entities\User;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Domain\ValueObjects\Email;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function save(User $user): User
    {
        $eloquent = EloquentUser::updateOrCreate(
            ['id' => $user->id()],
            [
                'name' => $user->name(),
                'email' => $user->email()->value(),
                'password' => $user->password()->hashedValue(),
                'is_active' => $user->isActive(),
                'force_password_change' => $user->forcePasswordChange(),
                'last_login_at' => $user->lastLoginAt()?->format('Y-m-d H:i:s'),
            ]
        );

        if (! empty($user->roles())) {
            $roleNames = array_map(fn ($role) => $role->name(), $user->roles());
            $spatieRoles = Role::whereIn('name', $roleNames)->get();

            $this->syncRoles($eloquent, $spatieRoles->pluck('id')->toArray());
        }

        return UserMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?User
    {
        $eloquent = EloquentUser::with('roles')->find($id);

        if ($eloquent === null) {
            return null;
        }

        return UserMapper::toDomain($eloquent);
    }

    public function findByEmail(Email $email): ?User
    {
        $eloquent = EloquentUser::with('roles')
            ->where('email', $email->value())
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return UserMapper::toDomain($eloquent);
    }

    public function delete(User $user): void
    {
        EloquentUser::where('id', $user->id())->delete();
    }

    public function search(array $filters = [], int $perPage = 25): array
    {
        $query = EloquentUser::with('roles');

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $filters['role']));
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $paginator->through(fn (EloquentUser $eloquent) => UserMapper::toDomain($eloquent));

        return $paginator->items();
    }

    public function count(): int
    {
        return EloquentUser::count();
    }

    private function syncRoles(EloquentUser $user, array $roleIds): void
    {
        $pivotTable = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key');

        $user->$morphKey = $user->getKey();

        $user->roles()->sync($roleIds);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
