<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

readonly class EmployeeExportDTO
{
    public function __construct(
        public ?string $search = null,
        public ?int $departmentId = null,
        public ?int $positionId = null,
        public ?int $employmentStatusId = null,
        public ?bool $isActive = null,
        public ?bool $isManager = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public array $selected = [],
        public bool $all = false,
        public string $format = 'csv',
    ) {}

    public static function fromArray(array $data): self
    {
        $format = in_array(($data['format'] ?? 'csv'), ['csv', 'excel'], true) ? (string) $data['format'] : 'csv';

        return new self(
            search: isset($data['search']) ? (string) $data['search'] : null,
            departmentId: isset($data['department_id']) && $data['department_id'] !== '' ? (int) $data['department_id'] : null,
            positionId: isset($data['position_id']) && $data['position_id'] !== '' ? (int) $data['position_id'] : null,
            employmentStatusId: isset($data['employment_status_id']) && $data['employment_status_id'] !== '' ? (int) $data['employment_status_id'] : null,
            isActive: isset($data['is_active']) && $data['is_active'] !== '' ? (bool) (int) $data['is_active'] : null,
            isManager: isset($data['is_manager']) && $data['is_manager'] !== '' ? (bool) (int) $data['is_manager'] : null,
            dateFrom: isset($data['date_from']) && $data['date_from'] !== '' ? (string) $data['date_from'] : null,
            dateTo: isset($data['date_to']) && $data['date_to'] !== '' ? (string) $data['date_to'] : null,
            selected: isset($data['selected']) && is_array($data['selected']) ? array_values(array_unique(array_map('intval', $data['selected']))) : [],
            all: isset($data['all']) ? (bool) (int) $data['all'] : false,
            format: $format,
        );
    }
}
