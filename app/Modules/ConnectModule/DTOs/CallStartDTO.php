<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

use Carbon\CarbonImmutable;

readonly class CallStartDTO
{
    public function __construct(
        public string $ciscoCallId,
        public string $queueName,
        public string $phoneNumber,
        public CarbonImmutable $ivrStartedAt,
    ) {}

    public static function fromCiscoWebhook(array $data): self
    {
        return new self(
            ciscoCallId: $data['call_id'],
            queueName: $data['queue_name'],
            phoneNumber: $data['ani'],
            ivrStartedAt: CarbonImmutable::parse($data['timestamp']),
        );
    }
}
