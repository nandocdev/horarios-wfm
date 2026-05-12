<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

use Carbon\CarbonImmutable;

readonly class CallCloseDTO
{
    public function __construct(
        public string $ciscoCallId,
        public CarbonImmutable $ivrEndedAt,
        public string $status = 'closed',
    ) {}

    public static function fromCiscoWebhook(array $data): self
    {
        return new self(
            ciscoCallId: $data['call_id'],
            ivrEndedAt: CarbonImmutable::parse($data['end_timestamp']),
            status: $data['call_status'] ?? 'closed',
        );
    }
}
