<?php

declare(strict_types=1);

namespace App\Src\Quality\Application\DTOs;

final readonly class SubmitEvaluationDTO
{
    public function __construct(
        public int $agentId,
        public int $evaluatorId,
        public int $formId,
        public array $scores,
        public ?string $comments = null,
    ) {}
}
