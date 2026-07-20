<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Actions;

use App\Modules\WorkflowsModule\DTOs\WorkflowDTO;
use App\Modules\WorkflowsModule\Events\WorkflowSubmitted;
use App\Modules\WorkflowsModule\Models\WorkflowApproval;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use Illuminate\Support\Facades\DB;

class SubmitWorkflowAction
{
    public function execute(WorkflowDTO $dto, array $approverIds = []): WorkflowRequest
    {
        return DB::transaction(function () use ($dto, $approverIds) {
            $workflow = WorkflowRequest::create([
                'requestable_type' => $dto->requestable_type,
                'requestable_id' => $dto->requestable_id,
                'requester_id' => $dto->requester_id,
                'type' => $dto->type,
                'status' => 'pending',
                'data' => $dto->data,
                'reason' => $dto->reason,
            ]);

            foreach ($approverIds as $order => $approverId) {
                WorkflowApproval::create([
                    'workflow_request_id' => $workflow->id,
                    'approver_id' => $approverId,
                    'step_order' => $order + 1,
                    'status' => 'pending',
                ]);
            }

            event(new WorkflowSubmitted($workflow));

            return $workflow;
        });
    }
}
