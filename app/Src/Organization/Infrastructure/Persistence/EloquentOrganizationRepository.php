<?php

declare(strict_types=1);

namespace App\Src\Organization\Infrastructure\Persistence;

use App\Src\Organization\Application\Mappers\OrganizationMapper;
use App\Src\Organization\Domain\Entities\Department;
use App\Src\Organization\Domain\Entities\Directorate;
use App\Src\Organization\Domain\Entities\Position;
use App\Src\Organization\Domain\Entities\Team;
use App\Src\Organization\Domain\Entities\TeamMember;
use App\Src\Organization\Domain\Repositories\OrganizationRepositoryInterface;

final class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    public function saveDirectorate(Directorate $directorate): Directorate
    {
        $eloquent = EloquentDirectorate::updateOrCreate(
            ['id' => $directorate->id()],
            [
                'name' => $directorate->name(),
                'description' => $directorate->description(),
                'is_active' => $directorate->isActive(),
            ],
        );

        return OrganizationMapper::directorateToDomain($eloquent);
    }

    public function findDirectorateById(int $id): ?Directorate
    {
        $eloquent = EloquentDirectorate::find($id);
        return $eloquent ? OrganizationMapper::directorateToDomain($eloquent) : null;
    }

    public function findAllDirectorates(): array
    {
        return EloquentDirectorate::all()
            ->map(fn (EloquentDirectorate $d) => OrganizationMapper::directorateToDomain($d))
            ->toArray();
    }

    public function saveDepartment(Department $department): Department
    {
        $eloquent = EloquentDepartment::updateOrCreate(
            ['id' => $department->id()],
            [
                'directorate_id' => $department->directorateId(),
                'name' => $department->name(),
                'description' => $department->description(),
            ],
        );

        return OrganizationMapper::departmentToDomain($eloquent);
    }

    public function findDepartmentById(int $id): ?Department
    {
        $eloquent = EloquentDepartment::find($id);
        return $eloquent ? OrganizationMapper::departmentToDomain($eloquent) : null;
    }

    public function findDepartmentsByDirectorate(int $directorateId): array
    {
        return EloquentDepartment::where('directorate_id', $directorateId)->get()
            ->map(fn (EloquentDepartment $d) => OrganizationMapper::departmentToDomain($d))
            ->toArray();
    }

    public function savePosition(Position $position): Position
    {
        $eloquent = EloquentPosition::updateOrCreate(
            ['id' => $position->id()],
            [
                'department_id' => $position->departmentId(),
                'name' => $position->name(),
                'description' => $position->description(),
                'position_code' => $position->positionCode(),
                'salary' => $position->salary(),
                'is_active' => $position->isActive(),
            ],
        );

        return OrganizationMapper::positionToDomain($eloquent);
    }

    public function findPositionById(int $id): ?Position
    {
        $eloquent = EloquentPosition::find($id);
        return $eloquent ? OrganizationMapper::positionToDomain($eloquent) : null;
    }

    public function findPositionsByDepartment(int $departmentId): array
    {
        return EloquentPosition::where('department_id', $departmentId)->get()
            ->map(fn (EloquentPosition $p) => OrganizationMapper::positionToDomain($p))
            ->toArray();
    }

    public function saveTeam(Team $team): Team
    {
        $eloquent = EloquentTeam::updateOrCreate(
            ['id' => $team->id()],
            [
                'name' => $team->name(),
                'description' => $team->description(),
                'supervisor_id' => $team->supervisorId(),
                'is_active' => $team->isActive(),
                'base_schedule_id' => $team->baseScheduleId(),
                'cisco_team_id' => $team->ciscoTeamId(),
            ],
        );

        $this->syncTeamMembers($eloquent, $team->memberIds());

        return OrganizationMapper::teamToDomain($eloquent, $team->memberIds());
    }

    public function findTeamById(int $id): ?Team
    {
        $eloquent = EloquentTeam::with('members')->find($id);
        if (! $eloquent) return null;

        $memberIds = $eloquent->members->where('is_active', true)->pluck('employee_id')->toArray();
        return OrganizationMapper::teamToDomain($eloquent, $memberIds);
    }

    public function findAllTeams(): array
    {
        return EloquentTeam::with('members')->get()
            ->map(fn (EloquentTeam $t) => OrganizationMapper::teamToDomain(
                $t,
                $t->members->where('is_active', true)->pluck('employee_id')->toArray(),
            ))
            ->toArray();
    }

    public function findTeamsBySupervisor(int $employeeId): array
    {
        return EloquentTeam::with('members')->where('supervisor_id', $employeeId)->get()
            ->map(fn (EloquentTeam $t) => OrganizationMapper::teamToDomain(
                $t,
                $t->members->where('is_active', true)->pluck('employee_id')->toArray(),
            ))
            ->toArray();
    }

    public function saveTeamMember(TeamMember $teamMember): TeamMember
    {
        $eloquent = EloquentTeamMember::updateOrCreate(
            [
                'team_id' => $teamMember->teamId(),
                'employee_id' => $teamMember->employeeId(),
            ],
            [
                'joined_at' => $teamMember->joinedAt()->format('Y-m-d'),
                'left_at' => $teamMember->leftAt()?->format('Y-m-d'),
                'is_active' => $teamMember->isActive(),
            ],
        );

        return OrganizationMapper::teamMemberToDomain($eloquent);
    }

    public function findActiveTeamMember(int $teamId, int $employeeId): ?TeamMember
    {
        $eloquent = EloquentTeamMember::where('team_id', $teamId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();

        return $eloquent ? OrganizationMapper::teamMemberToDomain($eloquent) : null;
    }

    public function deactivateTeamMembersByEmployee(int $employeeId): void
    {
        EloquentTeamMember::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'left_at' => now(),
            ]);
    }

    public function findTeamMembersByTeam(int $teamId): array
    {
        return EloquentTeamMember::where('team_id', $teamId)->get()
            ->map(fn (EloquentTeamMember $m) => OrganizationMapper::teamMemberToDomain($m))
            ->toArray();
    }

    public function findActiveTeamMembersByEmployee(int $employeeId): array
    {
        return EloquentTeamMember::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->get()
            ->map(fn (EloquentTeamMember $m) => OrganizationMapper::teamMemberToDomain($m))
            ->toArray();
    }

    private function syncTeamMembers(EloquentTeam $team, array $memberIds): void
    {
        $currentIds = EloquentTeamMember::where('team_id', $team->id)
            ->where('is_active', true)
            ->pluck('employee_id')
            ->toArray();

        $toAdd = array_diff($memberIds, $currentIds);
        $toRemove = array_diff($currentIds, $memberIds);

        if (! empty($toRemove)) {
            EloquentTeamMember::where('team_id', $team->id)
                ->whereIn('employee_id', $toRemove)
                ->update(['is_active' => false, 'left_at' => now()]);
        }

        foreach ($toAdd as $employeeId) {
            EloquentTeamMember::updateOrCreate(
                ['team_id' => $team->id, 'employee_id' => $employeeId],
                ['joined_at' => now(), 'is_active' => true, 'left_at' => null],
            );
        }
    }
}
