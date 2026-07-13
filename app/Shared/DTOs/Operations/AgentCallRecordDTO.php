<?php

declare(strict_types=1);

namespace App\Shared\DTOs\Operations;

use Spatie\LaravelData\Data;

final readonly class AgentCallRecordDTO extends Data
{
    public function __construct(
        public int $employee_id,
        public string $start_time,
        public ?string $end_time,
        public int $talk_time,
        public int $hold_time,
        public int $work_time,
        public string $phone_number,
        public ?string $csq_name,
        public ?string $call_type,
    ) {}
}
