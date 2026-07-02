<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Application\DTOs\RoleDTO;
use App\Src\Identity\Domain\Entities\Role;
use App\Src\Identity\Domain\Repositories\RoleRepositoryInterface;

final class UpdateRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $repository,
    ) {}

    public function handle(RoleDTO $dto): Role
    {
        $role = $this->repository->findById($dto->id);

        if ($role === null) {
            throw new \RuntimeException("Role with ID {$dto->id} not found.");
        }

        $role = Role::fromDatabase(
            id: $role->id(),
            name: $dto->name,
            code: $dto->code,
            hierarchyLevel: $dto->hierarchyLevel,
            guardName: $dto->guardName,
            permissions: $role->permissions(),
            createdAt: $role->createdAt(),
            updatedAt: new \DateTimeImmutable(),
        );

        return $this->repository->save($role);
    }
}
