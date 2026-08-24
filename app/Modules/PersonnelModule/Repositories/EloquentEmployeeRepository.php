<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Repositories;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Employees\EmployeeInterface;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function find(int $id): ?EmployeeInterface
    {
        return Employee::find($id);
    }

    public function findByUsername(string $username): ?EmployeeInterface
    {
        return Employee::where('username', $username)->first();
    }

    public function findActive(): array
    {
        return Employee::where('is_active', true)->get()->all();
    }

    public function findByTeam(int $teamId): array
    {
        return Employee::where('team_id', $teamId)->get()->all();
    }

    public function findActiveByTeams(array $teamIds): array
    {
        return Employee::where('is_active', true)
            ->whereIn('team_id', $teamIds)
            ->get()
            ->all();
    }

    public function findActiveByPositions(array $positionIds): array
    {
        return Employee::where('is_active', true)
            ->whereIn('position_id', $positionIds)
            ->get()
            ->all();
    }

    public function findAgentsByPositions(array $positionIds, ?int $teamId = null, ?string $search = null): array
    {
        return Employee::query()
            ->whereIn('position_id', $positionIds)
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->get()
            ->all();
    }

    public function findByUser(int $userId): ?EmployeeInterface
    {
        return Employee::where('user_id', $userId)->first();
    }

    public function getSubordinateIds(int $employeeId): array
    {
        $results = DB::select('
            WITH RECURSIVE subordinates_tree AS (
                SELECT id FROM employees WHERE parent_id = ?
                UNION ALL
                SELECT e.id FROM employees e
                INNER JOIN subordinates_tree st ON e.parent_id = st.id
            )
            SELECT id FROM subordinates_tree
        ', [$employeeId]);

        return array_map(fn ($row) => (int) $row->id, $results);
    }

    public function search(string $query): array
    {
        return Employee::where(function ($q) use ($query) {
            $q->where('first_name', 'ilike', "%{$query}%")
                ->orWhere('last_name', 'ilike', "%{$query}%")
                ->orWhere('username', 'ilike', "%{$query}%")
                ->orWhere('email', 'ilike', "%{$query}%");
        })->get()->all();
    }
}
