<?php

declare(strict_types=1);

namespace App\Src\Quality\Domain\Events;

use App\Src\Quality\Domain\Entities\AgentEvaluation;
use App\Src\Shared\Domain\Events\DomainEvent;

final class EvaluationCompleted extends DomainEvent
{
    public function __construct(
        public readonly AgentEvaluation $evaluation,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'quality.evaluation.completed';
    }
}
