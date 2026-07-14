<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\DTOs;

use Spatie\LaravelData\Data;

final class CreateCalibrationDTO extends Data
{
    public function __construct(
        public readonly string $evaluation_id,
        public readonly int $score_nuevo,
        public readonly int $created_by,
        public readonly ?string $obs = null,
    ) {}
}
