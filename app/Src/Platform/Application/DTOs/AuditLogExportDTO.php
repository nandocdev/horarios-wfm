<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class AuditLogExportDTO {
    public function __construct(
        public ?string $search = null,
        public ?string $action = null,
        public ?string $entityType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public string $format = 'csv',
    ) {}

    public function toFilterArray(): array {
        return [
            'search' => $this->search,
            'action' => $this->action,
            'entity_type' => $this->entityType,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }
}
