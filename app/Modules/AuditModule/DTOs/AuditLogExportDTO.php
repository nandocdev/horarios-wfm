<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\DTOs;

use Illuminate\Http\Request;

readonly class AuditLogExportDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $action = null,
        public ?string $entityType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public string $format = 'csv',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->query('search'),
            action: $request->query('action'),
            entityType: $request->query('entityType'),
            dateFrom: $request->query('dateFrom'),
            dateTo: $request->query('dateTo'),
            format: strtolower($request->query('format', 'csv')),
        );
    }
}
