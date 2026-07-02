<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Application\DTOs\PasswordResetDTO;
use App\Src\Identity\Domain\Events\UserPasswordReset;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Domain\Services\PasswordHasherInterface;
use App\Src\Identity\Domain\ValueObjects\Password;
use App\Src\Shared\Domain\ValueObjects\Email;

final class ResetUserPasswordHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $hasher,
    ) {}

    public function handle(PasswordResetDTO $dto): void
    {
        if ($dto->password !== $dto->passwordConfirmation) {
            throw new \InvalidArgumentException('Password and confirmation do not match.');
        }

        $email = new Email($dto->email);
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new \RuntimeException("User with email {$dto->email} not found.");
        }

        $password = Password::fromPlainText($dto->password, $this->hasher);
        $user->updatePassword($password);

        $this->userRepository->save($user);

        event(new UserPasswordReset($user));
    }
}
