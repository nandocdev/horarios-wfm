<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Actions;

use App\Modules\WorkflowsModule\Enums\WorkflowStatus;
use App\Modules\WorkflowsModule\Events\WorkflowApproved;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use Illuminate\Support\Facades\DB;

class ApproveWorkflowAction
{
    public function execute(WorkflowRequest $workflow, int $approverId, ?string $comment = null): WorkflowRequest
    {
        return DB::transaction(function () use ($workflow, $approverId, $comment) {
            $approval = $workflow->approvals()
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            $approval->update([
                'status' => 'approved',
                'comment' => $comment,
                'decided_at' => now(),
            ]);

            $pendingSteps = $workflow->approvals()
                ->where('status', 'pending')
                ->exists();

            if (! $pendingSteps) {
                $workflow->update(['status' => WorkflowStatus::Approved->value]);
                event(new WorkflowApproved($workflow));
            }

            return $workflow->fresh(['approvals']);
        });
    }
}
