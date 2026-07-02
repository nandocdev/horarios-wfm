<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\Handlers;

use App\Src\Organization\Application\DTOs\CreateTeamDTO;
use App\Src\Organization\Domain\Entities\Team;
use App\Src\Organization\Domain\Events\TeamCreated;
use App\Src\Organization\Domain\Repositories\OrganizationRepositoryInterface;
use App\Src\Organization\Domain\Specifications\TeamMaxSizeSpecification;

final class CreateTeamHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
        private TeamMaxSizeSpecification $maxSizeSpec,
    ) {}

    public function handle(CreateTeamDTO $dto): Team
    {
        $team = Team::create(
            name: $dto->name,
            description: $dto->description,
            supervisorId: $dto->supervisorId,
            baseScheduleId: $dto->baseScheduleId,
            ciscoTeamId: $dto->ciscoTeamId,
        );

        foreach ($dto->memberIds as $employeeId) {
            $team->assignMember($employeeId);
        }

        if (! $this->maxSizeSpec->isSatisfiedBy($team)) {
            throw new \DomainException($this->maxSizeSpec->message());
        }

        $saved = $this->repository->saveTeam($team);

        event(new TeamCreated($saved));

        return $saved;
    }
}
