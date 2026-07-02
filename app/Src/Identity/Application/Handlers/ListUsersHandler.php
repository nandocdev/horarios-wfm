<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Application\DTOs\UserFilterDTO;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;

final class ListUsersHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {}

    public function handle(UserFilterDTO $dto, int $perPage = 25): array
    {
        return $this->repository->search(
            filters: $dto->toArray(),
            perPage: $perPage,
        );
    }
}
