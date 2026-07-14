<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\DTOs;

use Spatie\LaravelData\Data;

final class CreateFeedbackDTO extends Data
{
    public function __construct(
        public readonly string $evaluation_id,
        public readonly string $obsfeed,
        public readonly int $created_by,
    ) {}
}
