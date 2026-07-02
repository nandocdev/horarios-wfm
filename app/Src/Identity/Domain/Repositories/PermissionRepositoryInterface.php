<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Repositories;

use App\Src\Identity\Domain\Entities\Permission;

interface PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission;

    public function findByName(string $name): ?Permission;

    public function all(): array;

    public function create(string $name, string $guardName = 'web'): Permission;

    public function syncToRole(int $roleId, array $permissionIds): void;
}
