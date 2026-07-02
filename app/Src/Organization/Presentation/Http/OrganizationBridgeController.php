<?php

declare(strict_types=1);

namespace App\Src\Organization\Presentation\Http;

use App\Src\Organization\Domain\Repositories\OrganizationRepositoryInterface;
use Illuminate\Http\JsonResponse;

final class OrganizationBridgeController
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {}

    public function getFlatStructure(): JsonResponse
    {
        $directorates = $this->repository->findAllDirectorates();
        $result = [];

        foreach ($directorates as $d) {
            $result[] = ['id' => $d->id(), 'name' => $d->name(), 'type' => 'directorate'];
            $departments = $this->repository->findDepartmentsByDirectorate($d->id());
            foreach ($departments as $dep) {
                $result[] = ['id' => $dep->id(), 'name' => $dep->name(), 'type' => 'department', 'parent_id' => $d->id()];
                $positions = $this->repository->findPositionsByDepartment($dep->id());
                foreach ($positions as $pos) {
                    $result[] = ['id' => $pos->id(), 'name' => $pos->name(), 'type' => 'position', 'parent_id' => $dep->id()];
                }
            }
        }

        return response()->json($result);
    }

    public function getDirectorates(): JsonResponse
    {
        $directorates = $this->repository->findAllDirectorates();
        return response()->json(array_map(fn ($d) => [
            'id' => $d->id(),
            'name' => $d->name(),
            'is_active' => $d->isActive(),
        ], $directorates));
    }

    public function getDepartments(int $directorateId): JsonResponse
    {
        $departments = $this->repository->findDepartmentsByDirectorate($directorateId);
        return response()->json(array_map(fn ($d) => [
            'id' => $d->id(),
            'name' => $d->name(),
            'directorate_id' => $d->directorateId(),
        ], $departments));
    }

    public function getTeams(): JsonResponse
    {
        $teams = $this->repository->findAllTeams();
        return response()->json(array_map(fn ($t) => [
            'id' => $t->id(),
            'name' => $t->name(),
            'supervisor_id' => $t->supervisorId(),
            'is_active' => $t->isActive(),
            'member_count' => $t->memberCount(),
        ], $teams));
    }
}
