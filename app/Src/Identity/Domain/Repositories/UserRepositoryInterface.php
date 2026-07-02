<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Repositories;

use App\Src\Identity\Domain\Entities\User;
use App\Src\Shared\Domain\ValueObjects\Email;

interface UserRepositoryInterface
{
    public function save(User $user): User;

    public function findById(int $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function delete(User $user): void;

    public function search(array $filters = [], int $perPage = 25): array;

    public function count(): int;
}
