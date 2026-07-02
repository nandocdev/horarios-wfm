<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Domain\Repositories\PermissionRepositoryInterface;

final class SyncRolePermissionsHandler
{
    public function __construct(
        private PermissionRepositoryInterface $repository,
    ) {}

    public function handle(int $roleId, array $permissionNames): void
    {
        $this->repository->syncToRole($roleId, $permissionNames);
    }
}
