<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Integrations;

final class NullIdentityValidator implements IdentityValidatorInterface
{
    public function verify(int $employeeId, string $credential): bool
    {
        return true;
    }

    public function verifyByExternalId(string $externalId, string $credential): bool
    {
        return true;
    }
}
