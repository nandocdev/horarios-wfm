<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\AgentSnapshotFilterDTO;
use App\Src\Connect\Domain\Entities\AgentState;
use App\Src\Connect\Domain\Repositories\CallEventRepositoryInterface;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

final readonly class FetchCiscoAgentSnapshotHandler
{
    public function __construct(
        private CallEventRepositoryInterface $repository,
    ) {}

    public function handle(AgentSnapshotFilterDTO $dto): array
    {
        $results = [];

        try {
            foreach ($dto->employeeIds as $employeeId) {
                $existing = $this->repository->findAgentState((int) $employeeId);

                $state = new AgentState(
                    id: $existing?->id(),
                    employeeId: (int) $employeeId,
                    externalId: $existing?->externalId() ?? (string) $employeeId,
                    currentState: $existing?->currentState() ?? 'UNKNOWN',
                    reasonCode: $existing?->reasonCode(),
                    lastChangedAt: $dto->date
                        ? new DateTimeImmutable($dto->date)
                        : new DateTimeImmutable(),
                    provider: new TelephonyProvider(TelephonyProvider::CISCO_FINESSE),
                    metadata: $existing?->metadata(),
                );

                $saved = $this->repository->saveAgentState($state);
                $results[] = $saved;
            }

            Log::info('Cisco agent snapshot fetched.', [
                'employee_count' => count($results),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch Cisco agent snapshot.', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $results;
    }
}
