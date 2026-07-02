<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\SyncEmployeeDTO;
use App\Src\Connect\Domain\Events\EmployeeProvisioned;
use App\Src\Connect\Domain\Ports\CiscoAprovisioningInterface;
use Illuminate\Support\Facades\Log;

final class SyncEmployeeWithCiscoHandler
{
    public function __construct(
        private CiscoAprovisioningInterface $cisco,
    ) {}

    public function handle(SyncEmployeeDTO $dto): array
    {
        try {
            $response = $this->cisco->syncEmployee($dto);

            event(new EmployeeProvisioned($dto, $response));

            Log::info("Employee {$dto->loginId} provisioned in Cisco.", [
                'login_id' => $dto->loginId,
            ]);

            return $response;

        } catch (\Throwable $e) {
            Log::error("Failed to provision employee {$dto->loginId} in Cisco.", [
                'login_id' => $dto->loginId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
