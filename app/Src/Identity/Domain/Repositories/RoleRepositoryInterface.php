<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Repositories;

use App\Src\Identity\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    public function save(Role $role): Role;

    public function findById(int $id): ?Role;

    public function findByName(string $name): ?Role;

    public function findByCode(string $code): ?Role;

    public function search(array $filters = [], int $perPage = 25): array;

    public function delete(Role $role): void;

    public function all(): array;
}
