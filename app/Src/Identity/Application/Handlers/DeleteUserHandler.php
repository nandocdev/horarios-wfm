<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;

final class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {}

    public function handle(int $userId): bool
    {
        $user = $this->repository->findById($userId);

        if ($user === null) {
            return false;
        }

        $this->repository->delete($user);

        return true;
    }
}
