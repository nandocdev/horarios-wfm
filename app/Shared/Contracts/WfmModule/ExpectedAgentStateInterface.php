<?php

declare(strict_types=1);

namespace App\Shared\Contracts\WfmModule;

use Carbon\CarbonInterface;

interface ExpectedAgentStateInterface
{
    public function execute(int $employeeId, ?CarbonInterface $now = null): array;

    public function executeBatch(array $employeeIds, ?CarbonInterface $now = null): array;
}
