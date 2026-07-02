<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Domain\Entities\User;
use App\Src\Identity\Domain\Events\UserActivated;
use App\Src\Identity\Domain\Events\UserDeactivated;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;

final class ToggleUserStatusHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {}

    public function handle(int $userId): User
    {
        $user = $this->repository->findById($userId);

        if ($user === null) {
            throw new \RuntimeException("User with ID {$userId} not found.");
        }

        if ($user->isActive()) {
            $user->deactivate();
            $user = $this->repository->save($user);
            event(new UserDeactivated($user));
        } else {
            $user->activate();
            $user = $this->repository->save($user);
            event(new UserActivated($user));
        }

        return $user;
    }
}
