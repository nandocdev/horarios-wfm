<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Ports;

use App\Src\Connect\Application\DTOs\SyncEmployeeDTO;
use App\Src\Connect\Application\DTOs\SyncTeamDTO;

interface CiscoAprovisioningInterface
{
    public function syncEmployee(SyncEmployeeDTO $dto): array;

    public function syncTeam(SyncTeamDTO $dto): array;

    public function removeEmployee(string $loginId): bool;

    public function testConnection(): bool;
}
