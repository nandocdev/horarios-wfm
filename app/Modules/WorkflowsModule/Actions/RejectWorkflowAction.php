<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Actions;

use App\Modules\WorkflowsModule\Enums\WorkflowStatus;
use App\Modules\WorkflowsModule\Events\WorkflowRejected;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use Illuminate\Support\Facades\DB;

class RejectWorkflowAction
{
    public function execute(WorkflowRequest $workflow, int $approverId, string $comment): WorkflowRequest
    {
        return DB::transaction(function () use ($workflow, $approverId, $comment) {
            $approval = $workflow->approvals()
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            $approval->update([
                'status' => 'rejected',
                'comment' => $comment,
                'decided_at' => now(),
            ]);

            $workflow->update(['status' => WorkflowStatus::Rejected->value]);

            event(new WorkflowRejected($workflow));

            return $workflow->fresh(['approvals']);
        });
    }
}
