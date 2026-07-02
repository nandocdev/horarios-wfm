<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\Handlers;

use App\Src\Wfm\Application\DTOs\RescheduleBreakDTO;
use App\Src\Wfm\Domain\Entities\IntradayActivity;
use App\Src\Wfm\Domain\Repositories\IntradayRepositoryInterface;

final class RescheduleBreakHandler
{
    public function __construct(
        private IntradayRepositoryInterface $repository,
    ) {}

    public function handle(RescheduleBreakDTO $dto): IntradayActivity
    {
        $overlapping = $this->repository->findOverlapping(
            $dto->employeeId,
            $dto->newStartTime,
            $dto->newEndTime,
        );

        if (! empty($overlapping)) {
            throw new \DomainException('El nuevo horario se traslapa con una actividad existente.');
        }

        $activity = IntradayActivity::create(
            employeeId: $dto->employeeId,
            activityTypeId: 0,
            startTime: $dto->newStartTime,
            endTime: $dto->newEndTime,
            notes: $dto->breakType === 'lunch' ? 'Almuerzo reprogramado' : 'Descanso reprogramado',
        );

        return $this->repository->saveActivity($activity);
    }
}
