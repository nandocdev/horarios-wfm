<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Application\DTOs\CreateUserDTO;
use App\Src\Identity\Domain\Entities\User;
use App\Src\Identity\Domain\Events\UserCreated;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Domain\Services\PasswordHasherInterface;
use App\Src\Identity\Domain\ValueObjects\IdentityRole;
use App\Src\Identity\Domain\ValueObjects\Password;

final class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $hasher,
    ) {}

    public function handle(CreateUserDTO $dto): User
    {
        $password = Password::fromPlainText($dto->password, $this->hasher);

        $roles = array_map(
            fn (array|IdentityRole $role) => $role instanceof IdentityRole
                ? $role
                : IdentityRole::fromArray(is_array($role) ? $role : ['name' => $role, 'code' => $role]),
            $dto->roles,
        );

        $user = User::create(
            name: $dto->name,
            email: $dto->email,
            password: $password,
            isActive: $dto->isActive,
            forcePasswordChange: $dto->forcePasswordChange,
            roles: $roles,
        );

        $user = $this->repository->save($user);

        event(new UserCreated($user));

        return $user;
    }
}
