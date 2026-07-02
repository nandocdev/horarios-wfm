<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Mappers;

use App\Src\Identity\Domain\Entities\User;
use App\Src\Identity\Domain\ValueObjects\IdentityRole;
use App\Src\Identity\Domain\ValueObjects\Password;
use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use App\Src\Shared\Domain\ValueObjects\Email;

final class UserMapper
{
    public static function toDomain(EloquentUser $eloquent): User
    {
        $roles = $eloquent->relationLoaded('roles')
            ? $eloquent->roles->map(fn ($role) => new IdentityRole(
                name: $role->name,
                code: $role->code ?? $role->name,
                hierarchyLevel: (int) ($role->hierarchy_level ?? 0),
                guardName: $role->guard_name ?? 'web',
            ))->toArray()
            : [];

        return User::fromDatabase(
            id: $eloquent->id,
            name: $eloquent->name,
            email: new Email($eloquent->email),
            password: Password::fromHash($eloquent->password),
            isActive: (bool) $eloquent->is_active,
            forcePasswordChange: (bool) $eloquent->force_password_change,
            lastLoginAt: $eloquent->last_login_at instanceof \DateTimeImmutable
                ? $eloquent->last_login_at
                : ($eloquent->last_login_at ? new \DateTimeImmutable($eloquent->last_login_at->format('Y-m-d H:i:s')) : null),
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                ? $eloquent->created_at
                : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
            updatedAt: $eloquent->updated_at instanceof \DateTimeImmutable
                ? $eloquent->updated_at
                : new \DateTimeImmutable($eloquent->updated_at->format('Y-m-d H:i:s')),
            roles: $roles,
        );
    }

    public static function toEloquent(User $user, ?EloquentUser $eloquent = null): EloquentUser
    {
        $model = $eloquent ?? new EloquentUser();

        $model->name = $user->name();
        $model->email = $user->email()->value();
        $model->password = $user->password()->hashedValue();
        $model->is_active = $user->isActive();
        $model->force_password_change = $user->forcePasswordChange();

        if ($user->lastLoginAt() !== null) {
            $model->last_login_at = $user->lastLoginAt()->format('Y-m-d H:i:s');
        }

        return $model;
    }
}
