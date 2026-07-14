<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\DTOs;

use Spatie\LaravelData\Data;

final class CriteriaAssignmentDTO extends Data
{
    public function __construct(
        public readonly string $queue_id,
        public readonly string $criteria_version_id,
        public readonly int $orden,
        public readonly bool $is_active = true,
    ) {}
}
