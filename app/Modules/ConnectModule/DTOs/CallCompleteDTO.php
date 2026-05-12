<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

readonly class CallCompleteDTO
{
    public function __construct(
        public string $citizenIdentifier,
        public int $caseSubtypeId,
        public int $queueId,
        public string $description,
        public ?int $employeeId = null,
    ) {}

    public static function fromForm(array $data): self
    {
        return new self(
            citizenIdentifier: $data['citizen_identifier'],
            caseSubtypeId: (int) $data['case_subtype_id'],
            queueId: (int) ($data['queue_id'] ?? 0),
            description: $data['description'],
            employeeId: $data['employee_id'] ?? null,
        );
    }
}
