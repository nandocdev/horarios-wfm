<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\Handlers;

use App\Src\Wfm\Application\DTOs\PublishWeeklyScheduleDTO;
use App\Src\Wfm\Domain\Entities\WeeklySchedule;
use App\Src\Wfm\Domain\Events\WeeklySchedulePublished;
use App\Src\Wfm\Domain\Repositories\ScheduleRepositoryInterface;

final class PublishWeeklyScheduleHandler
{
    public function __construct(
        private ScheduleRepositoryInterface $repository,
    ) {}

    public function handle(PublishWeeklyScheduleDTO $dto): WeeklySchedule
    {
        $weeklySchedule = $this->repository->findWeeklyScheduleById($dto->weeklyScheduleId);

        if ($weeklySchedule === null) {
            throw new \RuntimeException("WeeklySchedule #{$dto->weeklyScheduleId} not found.");
        }

        if ($weeklySchedule->isPublished()) {
            throw new \DomainException('La planificación semanal ya está publicada.');
        }

        $weeklySchedule->publish(new \DateTimeImmutable());

        $saved = $this->repository->saveWeeklySchedule($weeklySchedule);

        event(new WeeklySchedulePublished($saved, $dto->publishedByUserId));

        return $saved;
    }
}
