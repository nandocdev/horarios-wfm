<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\Handlers;

use App\Src\Wfm\Application\DTOs\AssignIntradayActivityDTO;
use App\Src\Wfm\Domain\Entities\IntradayActivity;
use App\Src\Wfm\Domain\Events\IntradayActivityAssigned;
use App\Src\Wfm\Domain\Repositories\IntradayRepositoryInterface;

final class AssignIntradayActivityHandler
{
    public function __construct(
        private IntradayRepositoryInterface $repository,
    ) {}

    public function handle(AssignIntradayActivityDTO $dto): IntradayActivity
    {
        $overlapping = $this->repository->findOverlapping(
            $dto->employeeId,
            $dto->startTime,
            $dto->endTime,
        );

        if (! empty($overlapping)) {
            throw new \DomainException('El empleado ya tiene una actividad programada en este horario.');
        }

        $activity = IntradayActivity::create(
            employeeId: $dto->employeeId,
            activityTypeId: $dto->activityTypeId,
            startTime: $dto->startTime,
            endTime: $dto->endTime,
            approvedPeriodId: $dto->approvedPeriodId,
            notes: $dto->notes,
        );

        $saved = $this->repository->saveActivity($activity);

        event(new IntradayActivityAssigned($saved));

        return $saved;
    }
}
