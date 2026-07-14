<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Events;

use App\Modules\QualityModule\Models\Evaluation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvaluationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Evaluation $evaluation,
    ) {}
}
