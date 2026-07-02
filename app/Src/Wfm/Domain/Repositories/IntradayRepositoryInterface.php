<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Repositories;

use App\Src\Wfm\Domain\Entities\ActivityType;
use App\Src\Wfm\Domain\Entities\ApprovedIntradayPeriod;
use App\Src\Wfm\Domain\Entities\IntradayActivity;
use DateTimeImmutable;

interface IntradayRepositoryInterface
{
    public function saveActivity(IntradayActivity $activity): IntradayActivity;
    public function findActivitiesByEmployee(int $employeeId, DateTimeImmutable $date): array;
    public function findOverlapping(int $employeeId, DateTimeImmutable $start, DateTimeImmutable $end): array;

    public function saveActivityType(ActivityType $type): ActivityType;
    public function findAllActivityTypes(): array;

    public function saveApprovedPeriod(ApprovedIntradayPeriod $period): ApprovedIntradayPeriod;
    public function findApprovedPeriodById(int $id): ?ApprovedIntradayPeriod;
    public function findApprovedPeriodsByTeam(int $teamId, DateTimeImmutable $date): array;
}
