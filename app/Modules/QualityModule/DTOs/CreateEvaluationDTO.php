<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\DTOs;

use Spatie\LaravelData\Data;

final class CreateEvaluationDTO extends Data
{
    /**
     * @param  array<array{criteria_version_id: string, puntaje: int}>  $scores
     * @param  array<array{red_flag_criteria_id: string}>  $red_flags
     */
    public function __construct(
        public readonly string $queue_id,
        public readonly int $employee_id,
        public readonly int $evaluator_id,
        public readonly ?int $clip_id = null,
        public readonly ?string $dtcall = null,
        public readonly ?string $tmcall = null,
        public readonly ?string $dteval = null,
        public readonly ?string $tmeval = null,
        public readonly ?string $callobs = null,
        public readonly array $scores = [],
        public readonly array $red_flags = [],
    ) {}
}
