<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Events;

use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WorkflowRequest $workflow,
    ) {}
}
