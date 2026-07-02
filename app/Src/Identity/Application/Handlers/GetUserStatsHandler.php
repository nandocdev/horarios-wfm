<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\Handlers;

use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;

final class GetUserStatsHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {}

    public function handle(): array
    {
        $allUsers = $this->repository->search(filters: [], perPage: 10000);

        $total = count($allUsers);
        $active = 0;
        $inactive = 0;
        $byRole = [];

        foreach ($allUsers as $user) {
            if ($user->isActive()) {
                $active++;
            } else {
                $inactive++;
            }

            foreach ($user->roles() as $role) {
                $roleCode = $role instanceof \App\Src\Identity\Domain\ValueObjects\IdentityRole
                    ? $role->code()
                    : (is_string($role) ? $role : 'unknown');

                if (! isset($byRole[$roleCode])) {
                    $byRole[$roleCode] = 0;
                }
                $byRole[$roleCode]++;
            }
        }

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'by_role' => $byRole,
        ];
    }
}
