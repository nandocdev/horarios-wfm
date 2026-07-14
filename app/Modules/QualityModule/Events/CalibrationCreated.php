<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Events;

use App\Modules\QualityModule\Models\CalibrationLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalibrationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CalibrationLog $calibration,
    ) {}
}
