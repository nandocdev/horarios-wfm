<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Domain\Repositories\RoleRepositoryInterface;

final class DeleteRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $repository,
    ) {}

    public function handle(int $roleId): bool
    {
        return $this->repository->delete($roleId);
    }
}
