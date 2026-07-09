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
}
