<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

use Illuminate\Support\Collection;

readonly class ReportPreviewResult
{
    public function __construct(
        public string $title,
        public string $description,
        public Collection $rows,
        public array $columns,
        public array $summary,
        public ?array $chartConfig = null,
    ) {}
}
