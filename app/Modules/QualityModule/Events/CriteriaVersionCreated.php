<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Events;

use App\Modules\QualityModule\Models\CriteriaVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriteriaVersionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CriteriaVersion $criteriaVersion,
    ) {}
}
