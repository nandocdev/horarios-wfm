<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\Mappers;

use App\Src\Organization\Domain\Entities\Department;
use App\Src\Organization\Domain\Entities\Directorate;
use App\Src\Organization\Domain\Entities\Position;
use App\Src\Organization\Domain\Entities\Team;
use App\Src\Organization\Domain\Entities\TeamMember;
use App\Src\Organization\Infrastructure\Persistence\EloquentDepartment;
use App\Src\Organization\Infrastructure\Persistence\EloquentDirectorate;
use App\Src\Organization\Infrastructure\Persistence\EloquentPosition;
use App\Src\Organization\Infrastructure\Persistence\EloquentTeam;
use App\Src\Organization\Infrastructure\Persistence\EloquentTeamMember;
use DateTimeImmutable;

final class OrganizationMapper
{
    public static function directorateToDomain(EloquentDirectorate $e): Directorate
    {
        return new Directorate(
            id: $e->id,
            name: $e->name,
            description: $e->description,
            isActive: (bool) $e->is_active,
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
        );
    }

    public static function departmentToDomain(EloquentDepartment $e): Department
    {
        return new Department(
            id: $e->id,
            directorateId: $e->directorate_id,
            name: $e->name,
            description: $e->description,
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
        );
    }

    public static function positionToDomain(EloquentPosition $e): Position
    {
        return new Position(
            id: $e->id,
            departmentId: $e->department_id,
            name: $e->name,
            description: $e->description,
            positionCode: $e->position_code,
            salary: $e->salary !== null ? (float) $e->salary : null,
            isActive: (bool) ($e->is_active ?? true),
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
        );
    }

    public static function teamToDomain(EloquentTeam $e, array $memberIds = []): Team
    {
        return new Team(
            id: $e->id,
            name: $e->name,
            description: $e->description,
            supervisorId: $e->supervisor_id,
            isActive: (bool) $e->is_active,
            baseScheduleId: $e->base_schedule_id,
            ciscoTeamId: $e->cisco_team_id,
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
            memberIds: $memberIds,
        );
    }

    public static function teamMemberToDomain(EloquentTeamMember $e): TeamMember
    {
        return new TeamMember(
            id: $e->id,
            teamId: $e->team_id,
            employeeId: $e->employee_id,
            joinedAt: self::toImmutable($e->joined_at),
            leftAt: $e->left_at ? self::toImmutable($e->left_at) : null,
            isActive: (bool) $e->is_active,
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
        );
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
