<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Application\Handlers;

use App\Src\TimeAndAttendance\Application\DTOs\ProcessPunchDTO;
use App\Src\TimeAndAttendance\Domain\Entities\AttendancePunch;
use App\Src\TimeAndAttendance\Domain\Events\EmployeePunched;
use App\Src\TimeAndAttendance\Domain\Repositories\AttendanceRepositoryInterface;

final class ProcessEmployeePunchHandler
{
    public function __construct(
        private AttendanceRepositoryInterface $repository,
    ) {}

    public function handle(ProcessPunchDTO $dto): AttendancePunch
    {
        $punch = AttendancePunch::create(
            employeeId: $dto->employeeId,
            type: $dto->type,
            punchedAt: $dto->punchedAt,
            source: $dto->source,
            externalId: $dto->externalId,
        );

        $saved = $this->repository->savePunch($punch);

        event(new EmployeePunched($saved));

        return $saved;
    }
}
