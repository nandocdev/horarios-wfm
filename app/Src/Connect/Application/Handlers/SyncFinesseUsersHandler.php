<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Entities\AgentState;
use App\Src\Connect\Domain\Repositories\CallEventRepositoryInterface;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

final readonly class SyncFinesseUsersHandler
{
    public function __construct(
        private CallEventRepositoryInterface $repository,
    ) {}

    public function handle(array $finesseUsers): int
    {
        $synced = 0;

        foreach ($finesseUsers as $user) {
            $employeeId = (int) ($user['employee_id'] ?? $user['userId'] ?? 0);
            if ($employeeId <= 0) {
                continue;
            }

            $existing = $this->repository->findAgentState($employeeId);

            $state = new AgentState(
                id: $existing?->id(),
                employeeId: $employeeId,
                externalId: $user['loginId'] ?? (string) $employeeId,
                currentState: $user['state'] ?? $user['current_state'] ?? 'UNKNOWN',
                reasonCode: $user['reason_code'] ?? $existing?->reasonCode(),
                lastChangedAt: isset($user['last_changed_at'])
                    ? new DateTimeImmutable($user['last_changed_at'])
                    : new DateTimeImmutable(),
                provider: new TelephonyProvider(TelephonyProvider::CISCO_FINESSE),
                metadata: $user,
            );

            $this->repository->saveAgentState($state);
            $synced++;
        }

        Log::info('Finesse users synced to agent states.', [
            'count' => $synced,
        ]);

        return $synced;
    }
}
