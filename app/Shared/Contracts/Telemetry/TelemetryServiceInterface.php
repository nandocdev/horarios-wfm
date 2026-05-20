<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Telemetry;

use App\Shared\DTOs\Telemetry\TelemetryStateDTO;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface TelemetryServiceInterface
{
    public function getCurrentState(int $employeeId): ?TelemetryStateDTO;

    public function getBatchCurrentStates(array $employeeIds): array;

    public function getStateTransitions(int $employeeId, CarbonInterface $start, CarbonInterface $end): Collection;
}
