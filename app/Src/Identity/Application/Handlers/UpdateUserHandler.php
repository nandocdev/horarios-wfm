<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Application\DTOs\UpdateUserDTO;
use App\Src\Identity\Domain\Entities\User;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Domain\Services\PasswordHasherInterface;
use App\Src\Identity\Domain\ValueObjects\IdentityRole;
use App\Src\Identity\Domain\ValueObjects\Password;

final class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $hasher,
    ) {}

    public function handle(UpdateUserDTO $dto): User
    {
        $user = $this->repository->findById($dto->id);

        if ($user === null) {
            throw new \RuntimeException("User with ID {$dto->id} not found.");
        }

        $user->rename($dto->name);

        if ($dto->password !== null) {
            $password = Password::fromPlainText($dto->password, $this->hasher);
            $user->updatePassword($password);
        }

        if ($dto->isActive && ! $user->isActive()) {
            $user->activate();
        } elseif (! $dto->isActive && $user->isActive()) {
            $user->deactivate();
        }

        if (! empty($dto->roles)) {
            $roles = array_map(
                fn ($role) => $role instanceof IdentityRole
                    ? $role
                    : IdentityRole::fromArray(is_array($role) ? $role : ['name' => $role, 'code' => $role]),
                $dto->roles,
            );

            $user = User::fromDatabase(
                id: $user->id(),
                name: $user->name(),
                email: $user->email(),
                password: $user->password(),
                isActive: $user->isActive(),
                forcePasswordChange: $user->forcePasswordChange(),
                lastLoginAt: $user->lastLoginAt(),
                createdAt: $user->createdAt(),
                updatedAt: new \DateTimeImmutable(),
                roles: $roles,
            );
        }

        return $this->repository->save($user);
    }
}
