<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Application\DTOs\RoleDTO;
use App\Src\Identity\Domain\Entities\Role;
use App\Src\Identity\Domain\Events\RoleCreated;
use App\Src\Identity\Domain\Repositories\RoleRepositoryInterface;

final class CreateRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $repository,
    ) {}

    public function handle(RoleDTO $dto): Role
    {
        $role = Role::create(
            name: $dto->name,
            code: $dto->code,
            hierarchyLevel: $dto->hierarchyLevel,
            guardName: $dto->guardName,
        );

        $role = $this->repository->save($role);

        event(new RoleCreated($role));

        return $role;
    }
}
