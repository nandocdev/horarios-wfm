<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Application\DTOs\AuthenticationResult;
use App\Src\Identity\Application\DTOs\LoginDTO;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Domain\Services\PasswordHasherInterface;

final class AuthenticateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $hasher,
    ) {}

    public function handle(LoginDTO $dto): AuthenticationResult
    {
        $user = $this->repository->findByEmail($dto->email);

        if ($user === null) {
            return AuthenticationResult::invalidCredentials();
        }

        if (! $user->isActive()) {
            return AuthenticationResult::accountDisabled();
        }

        if (! $user->password()->verify($dto->password, $this->hasher)) {
            return AuthenticationResult::invalidCredentials();
        }

        if ($user->forcePasswordChange()) {
            return AuthenticationResult::passwordExpired($user);
        }

        return AuthenticationResult::success($user);
    }
}
