<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\IncomingCallEventDTO;
use App\Src\Connect\Domain\Entities\CallEvent;
use App\Src\Connect\Domain\Events\CallEventReceived;
use App\Src\Connect\Domain\Repositories\CallEventRepositoryInterface;
use App\Src\Connect\Domain\Services\TelephonyNormalizationService;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;

final class ProcessCallEventHandler
{
    public function __construct(
        private TelephonyNormalizationService $normalizer,
        private CallEventRepositoryInterface $repository,
    ) {}

    public function handle(IncomingCallEventDTO $dto): CallEvent
    {
        $callEvent = match ($dto->provider) {
            TelephonyProvider::CISCO_FINESSE => $this->normalizer->normalizeCiscoWebhook($dto->payload),
            TelephonyProvider::AVAYA => $this->normalizer->normalizeAvayaWebhook($dto->payload),
            default => throw new \InvalidArgumentException("Unsupported provider: {$dto->provider}"),
        };

        $saved = $this->repository->saveCallEvent($callEvent);

        event(new CallEventReceived($saved));

        return $saved;
    }
}
