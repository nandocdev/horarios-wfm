<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

readonly class ManualCallRecordDTO
{
    public function __construct(
        public int $queueId,
        public string $phoneNumber,
        public string $citizenIdentifier,
        public int $caseSubtypeId,
        public string $description,
        public string $status,
        public ?int $employeeId = null,
    ) {}

    public static function fromForm(array $data): self
    {
        return new self(
            queueId: (int) ($data['queue_id'] ?? 0),
            phoneNumber: $data['phone_number'],
            citizenIdentifier: $data['citizen_identifier'],
            caseSubtypeId: (int) $data['case_subtype_id'],
            description: $data['description'],
            status: $data['status'],
            employeeId: $data['employee_id'] ?? null,
        );
    }
}
