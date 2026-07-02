<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\SyncTeamDTO;
use App\Src\Connect\Domain\Ports\CiscoAprovisioningInterface;
use Illuminate\Support\Facades\Log;

final class SyncTeamsWithCiscoHandler
{
    public function __construct(
        private CiscoAprovisioningInterface $cisco,
    ) {}

    public function handle(SyncTeamDTO $dto): array
    {
        try {
            $response = $this->cisco->syncTeam($dto);

            Log::info("Team {$dto->name} synced to Cisco.", [
                'team_id' => $dto->teamId,
            ]);

            return $response;

        } catch (\Throwable $e) {
            Log::error("Failed to sync team {$dto->name} to Cisco.", [
                'team_id' => $dto->teamId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
