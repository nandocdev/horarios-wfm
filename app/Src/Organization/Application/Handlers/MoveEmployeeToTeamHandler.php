<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\Handlers;

use App\Src\Organization\Application\DTOs\MoveEmployeeToTeamDTO;
use App\Src\Organization\Domain\Entities\TeamMember;
use App\Src\Organization\Domain\Events\EmployeeAssignedToTeam;
use App\Src\Organization\Domain\Repositories\OrganizationRepositoryInterface;

final class MoveEmployeeToTeamHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {}

    public function handle(MoveEmployeeToTeamDTO $dto): TeamMember
    {
        $this->repository->deactivateTeamMembersByEmployee($dto->employeeId);

        $teamMember = TeamMember::assign(
            teamId: $dto->targetTeamId,
            employeeId: $dto->employeeId,
            joinedAt: $dto->effectiveDate,
        );

        $saved = $this->repository->saveTeamMember($teamMember);

        event(new EmployeeAssignedToTeam($saved));

        return $saved;
    }
}
