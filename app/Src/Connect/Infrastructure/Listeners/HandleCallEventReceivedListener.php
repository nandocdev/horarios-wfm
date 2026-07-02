<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Listeners;

use App\Src\Connect\Domain\Entities\AgentState;
use App\Src\Connect\Domain\Events\CallEventReceived;
use App\Src\Connect\Domain\Repositories\CallEventRepositoryInterface;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

final class HandleCallEventReceivedListener
{
    public function __construct(
        private readonly CallEventRepositoryInterface $repository,
    ) {}

    public function handle(CallEventReceived $event): void
    {
        $callEvent = $event->event;

        if ($callEvent->employeeId() === null) {
            return;
        }

        try {
            $existing = $this->repository->findAgentState($callEvent->employeeId());

            $newState = match ($callEvent->status()->value()) {
                'ringing' => 'RINGING',
                'connected' => 'TALKING',
                'on_hold' => 'HOLD',
                'completed', 'closed', 'failed' => 'READY',
                default => $existing?->currentState() ?? 'UNKNOWN',
            };

            $state = new AgentState(
                id: $existing?->id(),
                employeeId: $callEvent->employeeId(),
                externalId: $existing?->externalId() ?? (string) $callEvent->employeeId(),
                currentState: $newState,
                reasonCode: $existing?->reasonCode(),
                lastChangedAt: new DateTimeImmutable(),
                provider: new TelephonyProvider(TelephonyProvider::CISCO_FINESSE),
                metadata: [
                    'source' => 'call_event',
                    'call_id' => $callEvent->externalCallId(),
                    'previous_state' => $existing?->currentState(),
                ],
            );

            $this->repository->saveAgentState($state);

            Log::info('Agent state updated from call event.', [
                'employee_id' => $callEvent->employeeId(),
                'state' => $newState,
                'call_status' => $callEvent->status()->value(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update agent state from call event.', [
                'employee_id' => $callEvent->employeeId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
