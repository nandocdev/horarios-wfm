<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Repositories;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Employees\EmployeeInterface;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;

final class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?EmployeeInterface
    {
        return Employee::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername(string $username): ?EmployeeInterface
    {
        return Employee::where('username', $username)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findActive(): array
    {
        return Employee::where('is_active', true)->get()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function findByTeam(int $teamId): array
    {
        return Employee::where('team_id', $teamId)->get()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveByTeams(array $teamIds): array
    {
        return Employee::where('is_active', true)
            ->whereIn('team_id', $teamIds)
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveByPositions(array $positionIds): array
    {
        return Employee::where('is_active', true)
            ->whereIn('position_id', $positionIds)
            ->get()
            ->all();
    }
}
