<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Repositories;

use App\Src\Organization\Domain\Entities\Department;
use App\Src\Organization\Domain\Entities\Directorate;
use App\Src\Organization\Domain\Entities\Position;
use App\Src\Organization\Domain\Entities\Team;
use App\Src\Organization\Domain\Entities\TeamMember;

interface OrganizationRepositoryInterface
{
    public function saveDirectorate(Directorate $directorate): Directorate;
    public function findDirectorateById(int $id): ?Directorate;
    public function findAllDirectorates(): array;

    public function saveDepartment(Department $department): Department;
    public function findDepartmentById(int $id): ?Department;
    public function findDepartmentsByDirectorate(int $directorateId): array;

    public function savePosition(Position $position): Position;
    public function findPositionById(int $id): ?Position;
    public function findPositionsByDepartment(int $departmentId): array;

    public function saveTeam(Team $team): Team;
    public function findTeamById(int $id): ?Team;
    public function findAllTeams(): array;
    public function findTeamsBySupervisor(int $employeeId): array;

    public function saveTeamMember(TeamMember $teamMember): TeamMember;
    public function findActiveTeamMember(int $teamId, int $employeeId): ?TeamMember;
    public function deactivateTeamMembersByEmployee(int $employeeId): void;
    public function findTeamMembersByTeam(int $teamId): array;
    public function findActiveTeamMembersByEmployee(int $employeeId): array;
}
