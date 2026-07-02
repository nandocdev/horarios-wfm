<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Listeners;

use App\Src\Connect\Domain\Events\EmployeeProvisioned;
use Illuminate\Support\Facades\Log;

final class HandleEmployeeProvisionedListener
{
    public function handle(EmployeeProvisioned $event): void
    {
        $dto = $event->dto;
        $response = $event->ciscoResponse;

        Log::info('Employee provisioned in Cisco Finesse.', [
            'login_id' => $dto->loginId,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'team_id' => $dto->teamId,
            'response_status' => $response['status'] ?? 'unknown',
        ]);
    }
}
