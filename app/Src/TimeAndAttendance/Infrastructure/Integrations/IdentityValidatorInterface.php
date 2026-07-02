<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Integrations;

interface IdentityValidatorInterface
{
    public function verify(int $employeeId, string $credential): bool;
    public function verifyByExternalId(string $externalId, string $credential): bool;
}
