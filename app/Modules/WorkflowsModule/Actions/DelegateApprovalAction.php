<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Actions;

use App\Modules\WorkflowsModule\Models\WorkflowDelegation;
use Illuminate\Support\Facades\DB;

class DelegateApprovalAction
{
    public function execute(int $originalApproverId, int $delegateId, string $startDate, string $endDate): WorkflowDelegation
    {
        return DB::transaction(function () use ($originalApproverId, $delegateId, $startDate, $endDate) {
            return WorkflowDelegation::create([
                'original_approver_id' => $originalApproverId,
                'delegate_id' => $delegateId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
            ]);
        });
    }
}
